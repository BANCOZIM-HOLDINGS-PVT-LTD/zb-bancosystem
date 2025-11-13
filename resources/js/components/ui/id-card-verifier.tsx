import React, { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Camera, RotateCcw, Check, AlertTriangle, CreditCard, Loader2, CheckCircle2 } from 'lucide-react';

interface IDCardVerifierProps {
    id?: string;
    onVerificationComplete: (data: IDVerificationResult) => void;
    onVerificationFailed?: (error: string) => void;
    label?: string;
    required?: boolean;
    error?: string;
    className?: string;
    disabled?: boolean;
}

export interface IDVerificationResult {
    verified: boolean;
    idNumber: string;
    firstName: string;
    lastName: string;
    dateOfBirth: string;
    cardType: 'metal' | 'plastic';
    expiryDate?: string;
    address?: string;
    confidence: number;
    ocrData: {
        raw: any;
        extractedFields: Record<string, any>;
    };
    biometricMatch?: boolean;
    faceImageUrl?: string;
}

const IDCardVerifier: React.FC<IDCardVerifierProps> = ({
    id,
    onVerificationComplete,
    onVerificationFailed,
    label = 'Verify National ID Card',
    required = false,
    error,
    className = '',
    disabled = false
}) => {
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [stream, setStream] = useState<MediaStream | null>(null);
    const [isStreaming, setIsStreaming] = useState(false);
    const [capturedImage, setCapturedImage] = useState<string>('');
    const [verificationStatus, setVerificationStatus] = useState<'idle' | 'capturing' | 'processing' | 'success' | 'failed'>('idle');
    const [verificationMessage, setVerificationMessage] = useState<string>('');
    const [detectedCardType, setDetectedCardType] = useState<'metal' | 'plastic' | null>(null);
    const [processingProgress, setProcessingProgress] = useState(0);
    const [verificationResult, setVerificationResult] = useState<IDVerificationResult | null>(null);

    const hasError = !!error;

    // Start camera for ID card capture
    const startCamera = async () => {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera not supported on this device');
            }

            const constraints = {
                video: {
                    facingMode: 'environment', // Back camera for document
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false
            };

            const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
            setStream(mediaStream);
            setIsStreaming(true);

            if (videoRef.current) {
                videoRef.current.srcObject = mediaStream;
            }
        } catch (err: any) {
            console.error('Camera access error:', err);
            let errorMessage = 'Camera access denied or not available';
            if (err.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied. Please allow camera access.';
            }
            setVerificationMessage(errorMessage);
            setVerificationStatus('failed');
            onVerificationFailed?.(errorMessage);
        }
    };

    // Stop camera
    const stopCamera = () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            setStream(null);
            setIsStreaming(false);
        }
    };

    // Capture ID card image
    const captureIDCard = () => {
        if (videoRef.current && canvasRef.current) {
            const canvas = canvasRef.current;
            const video = videoRef.current;

            if (video.videoWidth === 0 || video.videoHeight === 0) {
                setVerificationMessage('Camera not ready. Please wait and try again.');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.95);

                if (dataUrl && dataUrl.length > 1000) {
                    setCapturedImage(dataUrl);
                    stopCamera();
                    setVerificationStatus('capturing');
                    // Auto-start verification
                    verifyIDCard(dataUrl);
                } else {
                    setVerificationMessage('Failed to capture image. Please try again.');
                }
            }
        }
    };

    // Verify ID card using SDK (OCR + AI + Biometrics)
    const verifyIDCard = async (imageDataUrl: string) => {
        setVerificationStatus('processing');
        setVerificationMessage('Verifying ID card...');
        setProcessingProgress(0);

        try {
            // Convert data URL to blob
            const response = await fetch(imageDataUrl);
            const blob = await response.blob();

            // Create FormData for API submission
            const formData = new FormData();
            formData.append('id_card_image', blob, 'id_card.jpg');
            formData.append('country', 'ZW'); // Zimbabwe
            formData.append('document_type', 'NATIONAL_ID');

            // Progress simulation (replace with actual SDK progress events)
            const progressInterval = setInterval(() => {
                setProcessingProgress(prev => Math.min(prev + 10, 90));
            }, 300);

            // Call verification API
            // Replace this with your actual SDK endpoint
            const verificationResponse = await fetch('/api/verify-id-card', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: formData
            });

            clearInterval(progressInterval);
            setProcessingProgress(100);

            if (!verificationResponse.ok) {
                throw new Error('Verification failed. Please try again.');
            }

            const result = await verificationResponse.json();

            // Process verification result
            if (result.success && result.data.verified) {
                const verificationData: IDVerificationResult = {
                    verified: true,
                    idNumber: result.data.id_number,
                    firstName: result.data.first_name,
                    lastName: result.data.last_name,
                    dateOfBirth: result.data.date_of_birth,
                    cardType: result.data.card_type || 'plastic',
                    expiryDate: result.data.expiry_date,
                    address: result.data.address,
                    confidence: result.data.confidence || 0,
                    ocrData: {
                        raw: result.data.ocr_raw,
                        extractedFields: result.data.extracted_fields
                    },
                    biometricMatch: result.data.biometric_match,
                    faceImageUrl: result.data.face_image_url
                };

                setVerificationResult(verificationData);
                setDetectedCardType(verificationData.cardType);
                setVerificationStatus('success');
                setVerificationMessage(`ID verified successfully! (${verificationData.cardType === 'metal' ? 'Metal' : 'Plastic'} Card)`);
                onVerificationComplete(verificationData);
            } else {
                throw new Error(result.message || 'ID verification failed. Please ensure the card is clear and visible.');
            }
        } catch (err: any) {
            console.error('Verification error:', err);
            setVerificationStatus('failed');
            const errorMsg = err.message || 'Verification failed. Please try again.';
            setVerificationMessage(errorMsg);
            onVerificationFailed?.(errorMsg);
        }
    };

    // Retry capture
    const retryCapture = () => {
        setCapturedImage('');
        setVerificationStatus('idle');
        setVerificationMessage('');
        setDetectedCardType(null);
        setProcessingProgress(0);
        setVerificationResult(null);
        startCamera();
    };

    // Reset to initial state
    const reset = () => {
        setCapturedImage('');
        setVerificationStatus('idle');
        setVerificationMessage('');
        setDetectedCardType(null);
        setProcessingProgress(0);
        setVerificationResult(null);
        stopCamera();
    };

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            stopCamera();
        };
    }, []);

    return (
        <div className={cn('space-y-4', className)}>
            {label && (
                <Label htmlFor={id}>
                    {label} {required && <span className="text-red-600">*</span>}
                </Label>
            )}

            {/* Instructions */}
            {verificationStatus === 'idle' && !isStreaming && !capturedImage && (
                <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                        <CreditCard className="h-5 w-5 text-blue-600 mt-1 flex-shrink-0" />
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                ID Card Verification Instructions
                            </p>
                            <ul className="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                                <li>• Place your National ID card on a flat, well-lit surface</li>
                                <li>• Ensure the card is clearly visible and in focus</li>
                                <li>• Both metal and plastic ID cards are accepted</li>
                                <li>• All text on the card must be readable</li>
                                <li>• Avoid glare or shadows on the card</li>
                            </ul>
                            <Button
                                onClick={startCamera}
                                disabled={disabled}
                                className="mt-3 bg-blue-600 hover:bg-blue-700"
                            >
                                <Camera className="h-4 w-4 mr-2" />
                                Start ID Verification
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Camera View */}
            {isStreaming && !capturedImage && (
                <div className="space-y-4">
                    <div className="relative bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                        <video
                            ref={videoRef}
                            autoPlay
                            playsInline
                            muted
                            className="w-full rounded-lg"
                            style={{ maxHeight: '500px' }}
                        />
                        {/* ID Card Guide Overlay */}
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="relative">
                                <div
                                    className="border-4 border-emerald-500 rounded-lg"
                                    style={{
                                        width: '320px',
                                        height: '200px',
                                        boxShadow: '0 0 0 9999px rgba(0,0,0,0.5)'
                                    }}
                                >
                                    {/* Corner guides */}
                                    <div className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-white"></div>
                                    <div className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-white"></div>
                                    <div className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-white"></div>
                                    <div className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-white"></div>
                                </div>
                                <p className="text-white text-sm font-medium text-center mt-4 bg-black bg-opacity-60 px-3 py-1 rounded">
                                    Position ID card within the frame
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-center gap-3">
                        <Button
                            onClick={captureIDCard}
                            className="bg-emerald-600 hover:bg-emerald-700"
                        >
                            <Camera className="h-5 w-5 mr-2" />
                            Capture ID Card
                        </Button>
                        <Button onClick={stopCamera} variant="outline">
                            Cancel
                        </Button>
                    </div>

                    <canvas ref={canvasRef} style={{ display: 'none' }} />
                </div>
            )}

            {/* Captured Image Preview */}
            {capturedImage && verificationStatus !== 'success' && (
                <div className="space-y-4">
                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <img
                            src={capturedImage}
                            alt="Captured ID Card"
                            className="w-full max-w-md mx-auto rounded-lg border-2 border-gray-300"
                        />
                    </div>

                    {/* Processing Status */}
                    {verificationStatus === 'processing' && (
                        <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div className="flex items-center gap-3">
                                <Loader2 className="h-5 w-5 text-blue-600 animate-spin" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        {verificationMessage}
                                    </p>
                                    <div className="mt-2 w-full bg-blue-200 dark:bg-blue-900 rounded-full h-2">
                                        <div
                                            className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            style={{ width: `${processingProgress}%` }}
                                        />
                                    </div>
                                    <p className="text-xs text-blue-600 mt-1">
                                        Performing OCR, AI validation, and biometric checks...
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Failed Status */}
                    {verificationStatus === 'failed' && (
                        <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <AlertTriangle className="h-5 w-5 text-red-600 mt-0.5" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-red-900 dark:text-red-100">
                                        Verification Failed
                                    </p>
                                    <p className="text-xs text-red-700 dark:text-red-300 mt-1">
                                        {verificationMessage}
                                    </p>
                                </div>
                            </div>
                            <Button
                                onClick={retryCapture}
                                variant="outline"
                                className="mt-3 w-full border-red-300 text-red-700 hover:bg-red-50"
                            >
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Try Again
                            </Button>
                        </div>
                    )}
                </div>
            )}

            {/* Success Status */}
            {verificationStatus === 'success' && verificationResult && (
                <div className="space-y-4">
                    <div className="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                            <CheckCircle2 className="h-6 w-6 text-green-600 mt-0.5" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                    ID Card Verified Successfully!
                                </p>
                                <div className="mt-3 space-y-2 text-xs">
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <span className="text-gray-600 dark:text-gray-400">ID Number:</span>
                                            <p className="font-medium text-green-900 dark:text-green-100">{verificationResult.idNumber}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600 dark:text-gray-400">Card Type:</span>
                                            <p className="font-medium text-green-900 dark:text-green-100">
                                                {verificationResult.cardType === 'metal' ? 'Metal ID Card' : 'Plastic ID Card'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600 dark:text-gray-400">Name:</span>
                                            <p className="font-medium text-green-900 dark:text-green-100">
                                                {verificationResult.firstName} {verificationResult.lastName}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600 dark:text-gray-400">Confidence:</span>
                                            <p className="font-medium text-green-900 dark:text-green-100">
                                                {(verificationResult.confidence * 100).toFixed(0)}%
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 flex gap-2">
                                    <div className="flex items-center gap-1 text-xs text-green-700 dark:text-green-300">
                                        <Check className="h-3 w-3" />
                                        <span>OCR Verified</span>
                                    </div>
                                    <div className="flex items-center gap-1 text-xs text-green-700 dark:text-green-300">
                                        <Check className="h-3 w-3" />
                                        <span>AI Validated</span>
                                    </div>
                                    {verificationResult.biometricMatch && (
                                        <div className="flex items-center gap-1 text-xs text-green-700 dark:text-green-300">
                                            <Check className="h-3 w-3" />
                                            <span>Biometric Match</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    <Button
                        onClick={reset}
                        variant="outline"
                        className="w-full"
                    >
                        <RotateCcw className="h-4 w-4 mr-2" />
                        Verify Different ID
                    </Button>
                </div>
            )}

            {/* Error Display */}
            {hasError && (
                <p className="text-sm text-red-600 flex items-center gap-1">
                    <AlertTriangle className="h-4 w-4" />
                    {error}
                </p>
            )}
        </div>
    );
};

export default IDCardVerifier;