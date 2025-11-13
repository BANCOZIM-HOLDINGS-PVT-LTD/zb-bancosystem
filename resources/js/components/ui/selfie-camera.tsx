import React, { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Camera, RotateCcw, Check, AlertTriangle, User } from 'lucide-react';
import * as faceapi from 'face-api.js';

interface SelfieCameraProps {
  id?: string;
  value: string; // Base64 image data
  onChange: (value: string) => void;
  label?: string;
  required?: boolean;
  error?: string;
  className?: string;
  disabled?: boolean;
}

const SelfieCamera: React.FC<SelfieCameraProps> = ({
  id,
  value,
  onChange,
  label = 'Take Selfie',
  required = false,
  error,
  className = '',
  disabled = false
}) => {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [stream, setStream] = useState<MediaStream | null>(null);
  const [isStreaming, setIsStreaming] = useState(false);
  const [distanceGuidance, setDistanceGuidance] = useState<'too-close' | 'too-far' | 'perfect' | 'detecting'>('detecting');
  const [faceDetected, setFaceDetected] = useState(false);
  const [capturedImage, setCapturedImage] = useState<string>('');
  const [modelsLoaded, setModelsLoaded] = useState(false);
  const [faceQualityIssues, setFaceQualityIssues] = useState<string[]>([]);
  const detectionIntervalRef = useRef<NodeJS.Timeout | null>(null);

  const hasError = !!error;

  // Load face-api.js models on mount
  useEffect(() => {
    const loadModels = async () => {
      try {
        const MODEL_URL = '/models'; // You'll need to place models in public/models
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        setModelsLoaded(true);
      } catch (error) {
        console.error('Error loading face detection models:', error);
        // Fall back to basic detection if models fail to load
        setModelsLoaded(true);
      }
    };
    loadModels();
  }, []);

  // Initialize camera
  const startCamera = async () => {
    try {
      const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: { ideal: 640 },
          height: { ideal: 480 }
        }
      });
      
      setStream(mediaStream);
      if (videoRef.current) {
        videoRef.current.srcObject = mediaStream;
        setIsStreaming(true);
      }
    } catch (err) {
      console.error('Error accessing camera:', err);
    }
  };

  // Stop camera
  const stopCamera = () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      setStream(null);
      setIsStreaming(false);
      setDistanceGuidance('detecting');
      setFaceDetected(false);
    }
    if (detectionIntervalRef.current) {
      clearInterval(detectionIntervalRef.current);
      detectionIntervalRef.current = null;
    }
  };

  // Check brightness of the video frame
  const checkBrightness = (video: HTMLVideoElement): number => {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) return 128; // Default middle value

    ctx.drawImage(video, 0, 0);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    let brightness = 0;

    for (let i = 0; i < data.length; i += 4) {
      const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
      brightness += avg;
    }

    return brightness / (data.length / 4);
  };

  // Real face detection using face-api.js
  useEffect(() => {
    if (!isStreaming || !modelsLoaded || !videoRef.current) return;

    const detectFace = async () => {
      if (!videoRef.current) return;

      try {
        // Detect faces with landmarks
        const detections = await faceapi
          .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions({
            inputSize: 224,
            scoreThreshold: 0.5
          }))
          .withFaceLandmarks();

        const qualityIssues: string[] = [];

        if (detections) {
          setFaceDetected(true);

          const box = detections.detection.box;
          const videoWidth = videoRef.current.videoWidth;
          const videoHeight = videoRef.current.videoHeight;

          // Check face size (distance detection)
          const faceArea = box.width * box.height;
          const videoArea = videoWidth * videoHeight;
          const faceRatio = faceArea / videoArea;

          if (faceRatio < 0.08) {
            setDistanceGuidance('too-far');
            qualityIssues.push('Move closer to camera');
          } else if (faceRatio > 0.35) {
            setDistanceGuidance('too-close');
            qualityIssues.push('Move away from camera');
          } else {
            // Check centering
            const faceCenterX = box.x + box.width / 2;
            const faceCenterY = box.y + box.height / 2;
            const videoCenterX = videoWidth / 2;
            const videoCenterY = videoHeight / 2;

            const offsetX = Math.abs(faceCenterX - videoCenterX) / videoWidth;
            const offsetY = Math.abs(faceCenterY - videoCenterY) / videoHeight;

            if (offsetX > 0.15 || offsetY > 0.15) {
              qualityIssues.push('Center your face');
            }

            // Check brightness
            const brightness = checkBrightness(videoRef.current);
            if (brightness < 60) {
              qualityIssues.push('Improve lighting - too dark');
            } else if (brightness > 200) {
              qualityIssues.push('Reduce lighting - too bright');
            }

            // Check face angle (using landmarks)
            if (detections.landmarks) {
              const nose = detections.landmarks.getNose();
              const leftEye = detections.landmarks.getLeftEye();
              const rightEye = detections.landmarks.getRightEye();

              // Calculate eye distance to check for frontal view
              const eyeDistance = Math.sqrt(
                Math.pow(leftEye[0].x - rightEye[0].x, 2) +
                Math.pow(leftEye[0].y - rightEye[0].y, 2)
              );

              // If eyes are too close together, face is turned
              if (eyeDistance < box.width * 0.3) {
                qualityIssues.push('Face camera directly');
              }
            }

            if (qualityIssues.length === 0) {
              setDistanceGuidance('perfect');
            } else {
              setDistanceGuidance('detecting');
            }
          }

          setFaceQualityIssues(qualityIssues);
        } else {
          setFaceDetected(false);
          setDistanceGuidance('detecting');
          setFaceQualityIssues(['No face detected']);
        }
      } catch (error) {
        console.error('Face detection error:', error);
      }
    };

    // Run detection every 500ms
    detectionIntervalRef.current = setInterval(detectFace, 500);

    return () => {
      if (detectionIntervalRef.current) {
        clearInterval(detectionIntervalRef.current);
        detectionIntervalRef.current = null;
      }
    };
  }, [isStreaming, modelsLoaded]);

  // Capture photo
  const capturePhoto = () => {
    if (videoRef.current && canvasRef.current) {
      const video = videoRef.current;
      const canvas = canvasRef.current;
      const context = canvas.getContext('2d');

      if (context) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Flip the image horizontally for selfie effect
        context.scale(-1, 1);
        context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        setCapturedImage(imageData);
        onChange(imageData);
        stopCamera();
      }
    }
  };

  // Retake photo
  const retakePhoto = () => {
    setCapturedImage('');
    onChange('');
    startCamera();
  };

  // Clear photo
  const clearPhoto = () => {
    setCapturedImage('');
    onChange('');
    stopCamera();
  };

  // Get guidance message
  const getGuidanceMessage = () => {
    switch (distanceGuidance) {
      case 'too-close':
        return {
          message: 'Move further away from the camera',
          color: 'text-red-600',
          icon: AlertTriangle
        };
      case 'too-far':
        return {
          message: 'Move closer to the camera',
          color: 'text-amber-600',
          icon: AlertTriangle
        };
      case 'perfect':
        return {
          message: 'Perfect distance! You can take the photo now',
          color: 'text-green-600',
          icon: Check
        };
      default:
        return {
          message: 'Position your face in the frame',
          color: 'text-blue-600',
          icon: User
        };
    }
  };

  const guidance = getGuidanceMessage();
  const GuidanceIcon = guidance.icon;

  // Load existing image
  useEffect(() => {
    if (value && value !== capturedImage) {
      setCapturedImage(value);
    }
  }, [value]);

  return (
    <div className={cn('space-y-4', className)}>
      {label && (
        <Label className="flex items-center text-base font-medium">
          <Camera className="w-4 h-4 mr-2" />
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </Label>
      )}

      <div className="relative">
        {!capturedImage && !isStreaming && (
          <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
            <Camera className="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              Take a selfie for identity verification
            </p>
            <Button onClick={startCamera} disabled={disabled}>
              <Camera className="w-4 h-4 mr-2" />
              Start Camera
            </Button>
          </div>
        )}

        {isStreaming && (
          <div className="relative">
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="w-full max-w-md mx-auto rounded-lg border-2 border-gray-300 dark:border-gray-600"
            />
            
            {/* Face outline guide */}
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className={cn(
                "border-2 rounded-full w-48 h-48 border-dashed",
                faceDetected ? "border-green-400" : "border-gray-400"
              )}>
                {/* Distance guidance overlay */}
                <div className={cn(
                  "absolute inset-0 rounded-full transition-all duration-300",
                  distanceGuidance === 'too-close' && "border-4 border-red-400 animate-pulse",
                  distanceGuidance === 'too-far' && "border-4 border-amber-400 animate-pulse",
                  distanceGuidance === 'perfect' && "border-4 border-green-400 shadow-lg"
                )} />
              </div>
            </div>

            {/* Guidance message */}
            <div className="absolute bottom-4 left-0 right-0 text-center">
              <div className={cn(
                "inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-white dark:bg-gray-800 shadow-lg border",
                guidance.color
              )}>
                <GuidanceIcon className="w-4 h-4 mr-2" />
                {guidance.message}
              </div>
            </div>
          </div>
        )}

        {capturedImage && (
          <div className="relative">
            <img
              src={capturedImage}
              alt="Captured selfie"
              className="w-full max-w-md mx-auto rounded-lg border-2 border-green-300 dark:border-green-600"
            />
            <div className="absolute top-2 right-2 bg-green-100 dark:bg-green-800 text-green-600 dark:text-green-300 p-2 rounded-full">
              <Check className="w-4 h-4" />
            </div>
          </div>
        )}

        {/* Hidden canvas for capturing */}
        <canvas ref={canvasRef} className="hidden" />
      </div>

      {/* Action buttons */}
      <div className="flex gap-2 justify-center">
        {isStreaming && (
          <>
            <Button
              onClick={capturePhoto}
              disabled={distanceGuidance !== 'perfect' || !faceDetected}
              className="bg-green-600 hover:bg-green-700"
            >
              <Camera className="w-4 h-4 mr-2" />
              Capture Photo
            </Button>
            <Button onClick={stopCamera} variant="outline">
              Cancel
            </Button>
          </>
        )}

        {capturedImage && (
          <>
            <Button onClick={retakePhoto} variant="outline">
              <RotateCcw className="w-4 h-4 mr-2" />
              Retake
            </Button>
            <Button onClick={clearPhoto} variant="outline">
              Clear Photo
            </Button>
          </>
        )}
      </div>

      {/* Instructions */}
      <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h4 className="font-medium text-blue-900 dark:text-blue-100 mb-2">
          Selfie Guidelines:
        </h4>
        <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
          <li>• Ensure good lighting on your face</li>
          <li>• Look directly at the camera</li>
          <li>• Keep your face within the circular guide</li>
          <li>• Remove sunglasses and hats</li>
          <li>• Wait for the "perfect distance" message before capturing</li>
        </ul>
      </div>

      {/* Error message */}
      {hasError && (
        <div className="flex items-center text-red-600 dark:text-red-400">
          <AlertTriangle className="w-4 h-4 mr-2" />
          <span className="text-sm">{error}</span>
        </div>
      )}

      {/* Browser compatibility note */}
      <p className="text-xs text-gray-500 text-center">
        Camera access required. Please allow camera permissions when prompted.
      </p>
    </div>
  );
};

export default SelfieCamera;