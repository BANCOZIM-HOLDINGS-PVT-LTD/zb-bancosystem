import React, { useState, useRef, useCallback, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useDropzone } from 'react-dropzone';
import SignatureCanvas from 'react-signature-canvas';
import {
    Upload,
    Camera,
    File as FileIcon,
    X,
    Check,
    Eye,
    ChevronLeft,
    AlertCircle,
    CreditCard,
    Home,
    Edit3,
    Info,
    FileCheck,
    FileWarning,
    CheckCircle2
} from 'lucide-react';
import { validateZimbabweanID } from '@/utils/zimbabwean-id-validator';
import IDCardVerifier, { IDVerificationResult } from '@/components/ui/id-card-verifier';

interface DocumentUploadStepProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
    loading?: boolean;
}

interface UploadedFile {
    id: string;
    file: File;
    name: string;
    preview: string;
    type: string;
    size: number;
    status: 'uploading' | 'completed' | 'error' | 'validating';
    progress?: number;
    path?: string;
    validationErrors?: string[];
    uploadedAt?: string;
    lastModified?: number;
    securityHash?: string; // For file integrity verification
    mimeType?: string; // Actual MIME type detected
    dimensions?: { width: number; height: number }; // For image files
    pageCount?: number; // For PDF files
}

interface DocumentRequirement {
    id: string;
    name: string;
    description: string;
    icon: React.ReactNode;
    required: boolean;
    acceptedTypes: string[];
    maxSize: number; // in MB
}

const DocumentUploadStep: React.FC<DocumentUploadStepProps> = ({ data, onNext, onBack, loading }) => {
    const [uploadedFiles, setUploadedFiles] = useState<Record<string, UploadedFile[]>>({});
    const [selfieDataUrl, setSelfieDataUrl] = useState<string>('');
    const [signatureDataUrl, setSignatureDataUrl] = useState<string>('');
    const [showCamera, setShowCamera] = useState(false);
    const [activeDocumentCamera, setActiveDocumentCamera] = useState<string | null>(null);
    const [capturedDocumentPhotos, setCapturedDocumentPhotos] = useState<Record<string, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [nationalIdNumber, setNationalIdNumber] = useState<string>('');
    const [nationalIdValidated, setNationalIdValidated] = useState<boolean>(false);
    const [idVerificationData, setIdVerificationData] = useState<IDVerificationResult | null>(null);

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const idVideoRef = useRef<HTMLVideoElement>(null);
    const idCanvasRef = useRef<HTMLCanvasElement>(null);
    const signatureRef = useRef<SignatureCanvas>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const idStreamRef = useRef<MediaStream | null>(null);

    // Document requirements based on employer type
    const getDocumentRequirements = (): DocumentRequirement[] => {
        const baseRequirements: DocumentRequirement[] = [
            {
                id: 'national_id',
                name: 'National ID',
                description: 'Upload a clear photo or scan of your National ID (both sides if applicable)',
                icon: <CreditCard className="h-6 w-6 text-emerald-600" />,
                required: true,
                acceptedTypes: ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
                maxSize: 10
            },
            {
                id: 'payslip',
                name: 'Payslip',
                description: 'Upload your most recent payslip or salary statement',
                icon: <FileIcon className="h-6 w-6 text-emerald-600" />,
                required: true,
                acceptedTypes: ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
                maxSize: 10
            }
        ];

        return baseRequirements;
    };

    const documentRequirements = getDocumentRequirements();

    // Generate a unique ID for each file
    const generateFileId = () => {
        return Date.now().toString(36) + Math.random().toString(36).substring(2);
    };

    // Enhanced file validation with content inspection
    const validateFile = async (file: File): Promise<{errors: string[], metadata: any}> => {
        const errors: string[] = [];
        const metadata: any = {
            mimeType: file.type,
            lastModified: file.lastModified
        };

        // Check file size
        if (file.size > 10 * 1024 * 1024) { // 10MB max
            errors.push('File size exceeds 10MB limit');
        } else if (file.size < 1024) { // 1KB min
            errors.push('File is too small, may be corrupted');
        }

        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            errors.push('Invalid file type. Only JPG, PNG, and PDF are allowed');
        }

        // Check file name length
        if (file.name.length > 100) {
            errors.push('File name is too long (max 100 characters)');
        }

        // Check for special characters in filename
        const invalidCharsRegex = /[<>:"/\\|?*]/;
        if (invalidCharsRegex.test(file.name)) {
            errors.push('File name contains invalid characters');
        }

        // Deeper content validation based on file type
        try {
            if (file.type.startsWith('image/')) {
                // Validate image dimensions and content
                const dimensions = await getImageDimensions(file);
                metadata.dimensions = dimensions;

                // Check if image dimensions are reasonable
                if (dimensions.width < 100 || dimensions.height < 100) {
                    errors.push('Image is too small (minimum 100x100 pixels)');
                }

                if (dimensions.width > 5000 || dimensions.height > 5000) {
                    errors.push('Image is too large (maximum 5000x5000 pixels)');
                }
            } else if (file.type === 'application/pdf') {
                // Validate PDF content
                const pageCount = await getPdfPageCount(file);
                metadata.pageCount = pageCount;

                // Check if PDF has a reasonable number of pages
                if (pageCount === 0) {
                    errors.push('PDF file appears to be empty');
                }

                if (pageCount > 50) {
                    errors.push('PDF has too many pages (maximum 50 pages)');
                }
            }

            // Generate security hash for file integrity verification
            metadata.securityHash = await generateFileHash(file);

        } catch (error) {
            console.error('Error during deep file validation:', error);
            errors.push('Could not validate file content. The file may be corrupted.');
        }

        return { errors, metadata };
    };

    // Helper function to get image dimensions
    const getImageDimensions = (file: File): Promise<{width: number, height: number}> => {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                resolve({
                    width: img.width,
                    height: img.height
                });
                URL.revokeObjectURL(img.src); // Clean up
            };
            img.onerror = () => {
                reject(new Error('Failed to load image'));
                URL.revokeObjectURL(img.src); // Clean up
            };
            img.src = URL.createObjectURL(file);
        });
    };

    // Helper function to get PDF page count
    const getPdfPageCount = (file: File): Promise<number> => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (event) => {
                try {
                    const arrayBuffer = event.target?.result as ArrayBuffer;

                    // Simple PDF page count estimation
                    // This is a basic implementation - a real solution would use a PDF library
                    const text = new Uint8Array(arrayBuffer).reduce((data, byte) => {
                        return data + String.fromCharCode(byte);
                    }, '');

                    // Count occurrences of "/Page" in the PDF
                    const matches = text.match(/\/Type\s*\/Page[^s]/g);
                    const pageCount = matches ? matches.length : 0;

                    resolve(pageCount);
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    };

    // Generate a hash for file integrity verification
    const generateFileHash = async (file: File): Promise<string> => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = async (event) => {
                try {
                    const arrayBuffer = event.target?.result as ArrayBuffer;

                    // Use SubtleCrypto API if available
                    if (window.crypto && window.crypto.subtle) {
                        const hashBuffer = await window.crypto.subtle.digest('SHA-256', arrayBuffer);
                        const hashArray = Array.from(new Uint8Array(hashBuffer));
                        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                        resolve(hashHex);
                    } else {
                        // Fallback to a simple hash function
                        const text = new Uint8Array(arrayBuffer).reduce((data, byte) => {
                            return data + byte.toString(16).padStart(2, '0');
                        }, '');

                        // Take first 64 chars as a simple hash
                        resolve(text.substring(0, 64));
                    }
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    };

    // Enhanced file upload handler with improved validation and progress tracking
    const onDrop = useCallback(async (acceptedFiles: File[], rejectedFiles: any[], documentType: string) => {
        setErrors(prev => ({ ...prev, [documentType]: '' }));

        if (rejectedFiles.length > 0) {
            const error = rejectedFiles[0].errors[0];
            setErrors(prev => ({ ...prev, [documentType]: error.message }));
            return;
        }

        // Create initial file objects with 'validating' status
        const initialFiles = acceptedFiles.map((file) => {
            const fileId = generateFileId();
            return {
                id: fileId,
                file,
                name: file.name,
                preview: URL.createObjectURL(file),
                type: documentType,
                size: file.size,
                status: 'validating' as const,
                progress: 0,
                lastModified: file.lastModified
            };
        });

        // Add initial files to state
        setUploadedFiles(prev => ({
            ...prev,
            [documentType]: [...(prev[documentType] || []), ...initialFiles]
        }));

        // Process each file with validation and upload simulation
        initialFiles.forEach(async (fileObj) => {
            try {
                // Perform deep validation (this happens asynchronously)
                const { errors: validationErrors, metadata } = await validateFile(fileObj.file);

                // Update file with validation results
                setUploadedFiles(prev => {
                    const updatedFiles = { ...prev };
                    const fileList = [...(updatedFiles[documentType] || [])];
                    const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                    if (fileIndex !== -1) {
                        // If validation failed, update status to error
                        if (validationErrors.length > 0) {
                            fileList[fileIndex] = {
                                ...fileList[fileIndex],
                                status: 'error',
                                validationErrors,
                                ...metadata // Add metadata even for failed files
                            };
                            updatedFiles[documentType] = fileList;
                            return updatedFiles;
                        }

                        // If validation passed, update status to uploading and start progress
                        fileList[fileIndex] = {
                            ...fileList[fileIndex],
                            status: 'uploading',
                            progress: 0,
                            ...metadata
                        };
                        updatedFiles[documentType] = fileList;
                    }
                    return updatedFiles;
                });

                // If validation failed, don't proceed with upload
                if (validationErrors.length > 0) return;

                // Perform actual upload using FormData and fetch API
                const uploadFile = async () => {
                    try {
                        const formData = new FormData();
                        formData.append('file', fileObj.file);
                        formData.append('document_type', documentType);

                        // Add session ID if available in data
                        if (data.sessionId) {
                            formData.append('session_id', data.sessionId);
                        }

                        // Add national ID number for national_id documents
                        if (documentType === 'national_id' && nationalIdNumber) {
                            formData.append('national_id_number', nationalIdNumber);
                        }

                        // Create XMLHttpRequest for progress tracking
                        const xhr = new XMLHttpRequest();

                        // Track upload progress
                        xhr.upload.addEventListener('progress', (event) => {
                            if (event.lengthComputable) {
                                const progress = Math.round((event.loaded / event.total) * 100);

                                // Update progress in state
                                setUploadedFiles(prev => {
                                    const updatedFiles = { ...prev };
                                    const fileList = [...(updatedFiles[documentType] || [])];
                                    const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                                    if (fileIndex !== -1) {
                                        fileList[fileIndex] = {
                                            ...fileList[fileIndex],
                                            progress: Math.min(progress, 99) // Never reach 100 until complete
                                        };
                                        updatedFiles[documentType] = fileList;
                                    }
                                    return updatedFiles;
                                });
                            }
                        });

                        // Handle response
                        xhr.onload = () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    // Try to parse JSON response
                                    let response: any = {};
                                    const responseText = xhr.responseText.trim();

                                    if (responseText) {
                                        try {
                                            response = JSON.parse(responseText);
                                        } catch (parseError) {
                                            // If not JSON, treat as successful upload with basic data
                                            console.warn('Response is not JSON, treating as successful upload');
                                            response = {
                                                success: true,
                                                path: `uploads/${documentType}/${fileObj.file.name}`,
                                                message: 'File uploaded successfully'
                                            };
                                        }
                                    } else {
                                        // Empty response, treat as successful
                                        response = {
                                            success: true,
                                            path: `uploads/${documentType}/${fileObj.file.name}`,
                                            message: 'File uploaded successfully'
                                        };
                                    }

                                    // Update file status to completed
                                    setUploadedFiles(prev => {
                                        const updatedFiles = { ...prev };
                                        const fileList = [...(updatedFiles[documentType] || [])];
                                        const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                                        if (fileIndex !== -1) {
                                            fileList[fileIndex] = {
                                                ...fileList[fileIndex],
                                                status: 'completed',
                                                progress: 100,
                                                path: response.path || `uploads/${documentType}/${fileObj.file.name}`,
                                                uploadedAt: new Date().toISOString(),
                                                ...response.metadata // Add any additional metadata from server
                                            };
                                            updatedFiles[documentType] = fileList;
                                        }
                                        return updatedFiles;
                                    });
                                } catch (error) {
                                    console.error('Error handling successful response:', error);
                                    // Update file status to error
                                    setUploadedFiles(prev => {
                                        const updatedFiles = { ...prev };
                                        const fileList = [...(updatedFiles[documentType] || [])];
                                        const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                                        if (fileIndex !== -1) {
                                            fileList[fileIndex] = {
                                                ...fileList[fileIndex],
                                                status: 'error',
                                                validationErrors: ['Error processing server response']
                                            };
                                            updatedFiles[documentType] = fileList;
                                        }
                                        return updatedFiles;
                                    });
                                }
                            } else {
                                // Handle error response
                                let errorMessage = 'Upload failed';
                                const responseText = xhr.responseText.trim();

                                if (responseText) {
                                    // Check if response looks like JSON
                                    if (responseText.trim().startsWith('{') || responseText.trim().startsWith('[')) {
                                        try {
                                            const response = JSON.parse(responseText);
                                            errorMessage = response.message || response.error || 'Upload failed';
                                        } catch (e) {
                                            console.warn('Failed to parse JSON response:', responseText.substring(0, 100));
                                            errorMessage = 'Server returned invalid response';
                                        }
                                    } else {
                                        // Non-JSON response, likely an error page
                                        console.warn('Non-JSON response received:', responseText.substring(0, 100));

                                        // Try to extract useful error message from HTML
                                        const tempDiv = document.createElement('div');
                                        tempDiv.innerHTML = responseText;
                                        const textContent = tempDiv.textContent || tempDiv.innerText || '';

                                        if (textContent.length < 200) {
                                            errorMessage = textContent.trim() || `Upload failed with status ${xhr.status}`;
                                        } else {
                                            errorMessage = `Upload failed with status ${xhr.status}`;
                                        }
                                    }
                                } else {
                                    errorMessage = `Upload failed with status ${xhr.status}`;
                                }

                                // Update file status to error
                                setUploadedFiles(prev => {
                                    const updatedFiles = { ...prev };
                                    const fileList = [...(updatedFiles[documentType] || [])];
                                    const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                                    if (fileIndex !== -1) {
                                        fileList[fileIndex] = {
                                            ...fileList[fileIndex],
                                            status: 'error',
                                            validationErrors: [errorMessage]
                                        };
                                        updatedFiles[documentType] = fileList;
                                    }
                                    return updatedFiles;
                                });
                            }
                        };

                        // Handle network errors
                        xhr.onerror = () => {
                            setUploadedFiles(prev => {
                                const updatedFiles = { ...prev };
                                const fileList = [...(updatedFiles[documentType] || [])];
                                const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                                if (fileIndex !== -1) {
                                    fileList[fileIndex] = {
                                        ...fileList[fileIndex],
                                        status: 'error',
                                        validationErrors: ['Network error occurred during upload']
                                    };
                                    updatedFiles[documentType] = fileList;
                                }
                                return updatedFiles;
                            });
                        };

                        // Open connection and send data
                        xhr.open('POST', '/api/documents/upload');

                        // Set headers
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (csrfToken) {
                            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                        }

                        // Accept JSON response
                        xhr.setRequestHeader('Accept', 'application/json');

                        // Log the request details for debugging
                        console.log('Uploading file:', {
                            filename: fileObj.file.name,
                            size: fileObj.file.size,
                            type: fileObj.file.type,
                            documentType: documentType,
                            sessionId: data.sessionId,
                            csrfToken: csrfToken ? 'present' : 'missing'
                        });

                        xhr.send(formData);
                    } catch (error) {
                        console.error('Error uploading file:', error);

                        // Update file with error status
                        setUploadedFiles(prev => {
                            const updatedFiles = { ...prev };
                            const fileList = [...(updatedFiles[documentType] || [])];
                            const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                            if (fileIndex !== -1) {
                                fileList[fileIndex] = {
                                    ...fileList[fileIndex],
                                    status: 'error',
                                    validationErrors: ['An unexpected error occurred during upload']
                                };
                                updatedFiles[documentType] = fileList;
                            }
                            return updatedFiles;
                        });
                    }
                };

                // Start the upload process
                uploadFile();
            } catch (error) {
                console.error('Error processing file:', error);

                // Update file with error status
                setUploadedFiles(prev => {
                    const updatedFiles = { ...prev };
                    const fileList = [...(updatedFiles[documentType] || [])];
                    const fileIndex = fileList.findIndex(f => f.id === fileObj.id);

                    if (fileIndex !== -1) {
                        fileList[fileIndex] = {
                            ...fileList[fileIndex],
                            status: 'error',
                            validationErrors: ['An unexpected error occurred while processing the file.']
                        };
                        updatedFiles[documentType] = fileList;
                    }
                    return updatedFiles;
                });
            }
        });
    }, [data.sessionId]); // Add data.sessionId as dependency

    // Enhanced remove file function with server-side deletion - using file ID instead of index
    const removeFile = (documentType: string, fileId: string) => {
        const fileToRemove = uploadedFiles[documentType]?.find(f => f.id === fileId);

        // If the file has been uploaded to the server, delete it there too
        if (fileToRemove && fileToRemove.status === 'completed' && fileToRemove.path) {
            try {
                // Call the delete API endpoint
                fetch('/api/documents/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ path: fileToRemove.path })
                })
                .then(response => {
                    if (!response.ok) {
                        console.error('Failed to delete file from server:', response.statusText);
                    }
                })
                .catch(error => {
                    console.error('Error deleting file from server:', error);
                });
            } catch (error) {
                console.error('Error deleting file:', error);
            }
        }

        // Remove from local state by file ID
        setUploadedFiles(prev => ({
            ...prev,
            [documentType]: prev[documentType]?.filter(f => f.id !== fileId) || []
        }));
    };

    // Effect to handle video stream when camera is shown
    useEffect(() => {
        if (showCamera && streamRef.current && videoRef.current) {
            console.log('Attaching stream to video element');
            videoRef.current.srcObject = streamRef.current;

            const video = videoRef.current;

            const handleLoadedMetadata = async () => {
                console.log('Camera stream metadata loaded');
                try {
                    await video.play();
                    console.log('Video playback started');
                } catch (playError) {
                    console.error('Error playing video:', playError);
                    setErrors(prev => ({ ...prev, selfie: 'Failed to start camera preview' }));
                }
            };

            video.addEventListener('loadedmetadata', handleLoadedMetadata);

            // Try to play immediately if metadata already loaded
            if (video.readyState >= 2) {
                video.play().then(() => {
                    console.log('Video playback started immediately');
                }).catch(err => {
                    console.log('Immediate play failed, waiting for metadata');
                });
            }

            return () => {
                video.removeEventListener('loadedmetadata', handleLoadedMetadata);
            };
        }
    }, [showCamera]);

    // Effect to handle document camera video stream
    useEffect(() => {
        if (activeDocumentCamera && idStreamRef.current && idVideoRef.current) {
            console.log(`Attaching stream to document video element for ${activeDocumentCamera}`);
            idVideoRef.current.srcObject = idStreamRef.current;

            const video = idVideoRef.current;

            const handleLoadedMetadata = async () => {
                console.log(`${activeDocumentCamera} camera stream metadata loaded`);
                try {
                    await video.play();
                    console.log(`${activeDocumentCamera} video playback started`);
                } catch (playError) {
                    console.error(`Error playing ${activeDocumentCamera} video:`, playError);
                    setErrors(prev => ({ ...prev, [activeDocumentCamera]: 'Failed to start camera preview' }));
                }
            };

            video.addEventListener('loadedmetadata', handleLoadedMetadata);

            // Try to play immediately if metadata already loaded
            if (video.readyState >= 2) {
                video.play().then(() => {
                    console.log(`${activeDocumentCamera} video playback started immediately`);
                }).catch(err => {
                    console.log(`${activeDocumentCamera} immediate play failed, waiting for metadata`);
                });
            }

            return () => {
                video.removeEventListener('loadedmetadata', handleLoadedMetadata);
            };
        }
    }, [activeDocumentCamera]);

    // Camera functionality
    const startCamera = async () => {
        setErrors(prev => ({ ...prev, selfie: '' }));

        try {
            // Check if mediaDevices is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera not supported on this device');
            }

            console.log('Requesting camera access...');

            const constraints = {
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('Camera access granted, stream obtained');

            // Store stream and show camera - useEffect will attach it
            streamRef.current = stream;
            setShowCamera(true);
        } catch (error: any) {
            console.error('Camera access error:', error);
            let errorMessage = 'Camera access denied or not available';

            if (error.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied. Please allow camera access and try again.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera found on this device.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'Camera is already in use by another application.';
            } else if (error.name === 'OverconstrainedError') {
                errorMessage = 'Camera does not support the required settings.';
            }

            setErrors(prev => ({ ...prev, selfie: errorMessage }));
        }
    };

    const capturePhoto = () => {
        if (videoRef.current && canvasRef.current) {
            const canvas = canvasRef.current;
            const video = videoRef.current;

            // Check if video has loaded properly
            if (video.videoWidth === 0 || video.videoHeight === 0) {
                setErrors(prev => ({ ...prev, selfie: 'Camera not ready. Please wait and try again.' }));
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            if (ctx) {
                try {
                    // Draw the video frame to canvas
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    // Convert to data URL with good quality
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);

                    // Validate the captured image
                    if (dataUrl && dataUrl.length > 1000) { // Basic check for valid image data
                        setSelfieDataUrl(dataUrl);
                        stopCamera();
                        console.log('Selfie captured successfully');
                    } else {
                        setErrors(prev => ({ ...prev, selfie: 'Failed to capture photo. Please try again.' }));
                    }
                } catch (error) {
                    console.error('Error capturing photo:', error);
                    setErrors(prev => ({ ...prev, selfie: 'Error capturing photo. Please try again.' }));
                }
            }
        } else {
            setErrors(prev => ({ ...prev, selfie: 'Camera not available. Please try again.' }));
        }
    };

    const stopCamera = () => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach(track => track.stop());
            streamRef.current = null;
        }
        setShowCamera(false);
    };

    const retakeSelfie = () => {
        setSelfieDataUrl('');
        startCamera();
    };

    // Generic Document Camera functionality for all document types
    const startDocumentCamera = async (documentType: string) => {
        setErrors(prev => ({ ...prev, [documentType]: '' }));

        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera not supported on this device');
            }

            console.log(`Requesting camera access for ${documentType}...`);

            const constraints = {
                video: {
                    facingMode: 'environment', // Use back camera on mobile
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log(`${documentType} camera access granted, stream obtained`);

            // Store stream and show camera - useEffect will attach it
            idStreamRef.current = stream;
            setActiveDocumentCamera(documentType);
        } catch (error: any) {
            console.error(`${documentType} camera access error:`, error);
            let errorMessage = 'Camera access denied or not available';

            if (error.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied. Please allow camera access and try again.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera found on this device.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'Camera is already in use by another application.';
            } else if (error.name === 'OverconstrainedError') {
                errorMessage = 'Camera does not support the required settings.';
            }

            setErrors(prev => ({ ...prev, [documentType]: errorMessage }));
        }
    };

    const captureDocumentPhoto = (documentType: string) => {
        console.log(`Attempting to capture photo for ${documentType}`);

        if (!idVideoRef.current || !idCanvasRef.current) {
            console.error('Video or canvas ref not available');
            setErrors(prev => ({ ...prev, [documentType]: 'Camera not available. Please try again.' }));
            return;
        }

        const canvas = idCanvasRef.current;
        const video = idVideoRef.current;

        console.log(`Video dimensions: ${video.videoWidth}x${video.videoHeight}`);

        if (video.videoWidth === 0 || video.videoHeight === 0) {
            console.error('Video not ready - dimensions are 0');
            setErrors(prev => ({ ...prev, [documentType]: 'Camera not ready. Please wait a moment and try again.' }));
            return;
        }

        // Set canvas size to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            console.error('Could not get canvas context');
            setErrors(prev => ({ ...prev, [documentType]: 'Canvas error. Please try again.' }));
            return;
        }

        try {
            console.log('Drawing video to canvas...');
            // Draw the current video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            console.log('Video drawn to canvas successfully');

            // Convert canvas to data URL (synchronous, more reliable)
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            console.log('Canvas converted to data URL, length:', dataUrl.length);

            // Validate the captured image
            if (dataUrl && dataUrl.length > 1000) {
                // Store the captured photo for preview
                setCapturedDocumentPhotos(prev => ({ ...prev, [documentType]: dataUrl }));

                // Stop the camera
                stopDocumentCamera();
                console.log(`${documentType} photo captured successfully and ready for preview`);
            } else {
                console.error('Data URL is invalid or too small');
                setErrors(prev => ({ ...prev, [documentType]: 'Failed to capture photo. Please try again.' }));
            }
        } catch (error) {
            console.error(`Error capturing ${documentType} photo:`, error);
            setErrors(prev => ({ ...prev, [documentType]: 'Error capturing photo. Please try again.' }));
        }
    };

    // Upload the captured document photo after user confirms
    const uploadCapturedDocumentPhoto = async (documentType: string) => {
        const dataUrl = capturedDocumentPhotos[documentType];
        if (!dataUrl) {
            setErrors(prev => ({ ...prev, [documentType]: 'No photo to upload' }));
            return;
        }

        try {
            console.log(`Converting data URL to file for upload: ${documentType}`);

            // Convert data URL to blob
            const response = await fetch(dataUrl);
            const blob = await response.blob();

            // Create a File object from the blob
            const timestamp = Date.now();
            const file = new File([blob], `${documentType}_${timestamp}.jpg`, {
                type: 'image/jpeg',
                lastModified: timestamp
            });

            console.log('File created from data URL:', file.name, file.size, 'bytes');

            // Process the captured image through the normal upload flow
            console.log('Starting upload process...');
            await onDrop([file], [], documentType);

            // Clear the captured photo from preview state
            setCapturedDocumentPhotos(prev => {
                const updated = { ...prev };
                delete updated[documentType];
                return updated;
            });

            console.log(`${documentType} photo uploaded successfully`);
        } catch (error) {
            console.error('Error uploading captured photo:', error);
            setErrors(prev => ({ ...prev, [documentType]: 'Error uploading photo. Please try again.' }));
        }
    };

    // Retake document photo
    const retakeDocumentPhoto = (documentType: string) => {
        setCapturedDocumentPhotos(prev => {
            const updated = { ...prev };
            delete updated[documentType];
            return updated;
        });
        // Restart camera
        startDocumentCamera(documentType);
    };

    const stopDocumentCamera = () => {
        if (idStreamRef.current) {
            idStreamRef.current.getTracks().forEach(track => track.stop());
            idStreamRef.current = null;
        }
        setActiveDocumentCamera(null);
    };

    // Signature functionality
    const clearSignature = () => {
        if (signatureRef.current) {
            signatureRef.current.clear();
            setSignatureDataUrl('');
        }
    };

    const saveSignature = () => {
        if (signatureRef.current && !signatureRef.current.isEmpty()) {
            const dataUrl = signatureRef.current.toDataURL('image/png');
            setSignatureDataUrl(dataUrl);
        }
    };

    // Enhanced validation with detailed checks
    const validateDocuments = (): boolean => {
        const newErrors: Record<string, string> = {};

        console.log('Validating documents:', {
            documentRequirements: documentRequirements.map(r => r.id),
            uploadedFiles: Object.keys(uploadedFiles),
            filesByType: Object.entries(uploadedFiles).reduce((acc, [type, files]) => {
                acc[type] = files.map(f => ({ id: f.id, status: f.status, name: f.file.name }));
                return acc;
            }, {} as Record<string, any[]>)
        });

        // Check required documents
        documentRequirements.forEach(req => {
            if (req.required) {
                // Check if documents exist for this requirement
                if (!uploadedFiles[req.id] || uploadedFiles[req.id].length === 0) {
                    newErrors[req.id] = `${req.name} is required`;
                } else {
                    const files = uploadedFiles[req.id];

                    // Check if there are any completed files
                    const hasCompletedFiles = files.some(file => file.status === 'completed');

                    // Check if any documents are still uploading or validating
                    const hasIncompleteFiles = files.some(
                        file => file.status === 'uploading' || file.status === 'validating'
                    );

                    // Check if any documents have errors
                    const hasErrorFiles = files.some(file => file.status === 'error');

                    // Determine validation status
                    if (!hasCompletedFiles) {
                        if (hasIncompleteFiles) {
                            newErrors[req.id] = `Please wait for ${req.name} to finish uploading`;
                        } else if (hasErrorFiles) {
                            newErrors[req.id] = `Please fix the errors with ${req.name} or upload a new file`;
                        } else {
                            newErrors[req.id] = `${req.name} is required`;
                        }
                    } else {
                        // Has completed files, but check if there are still issues
                        if (hasIncompleteFiles) {
                            // Don't show error if we have at least one completed file
                            // Just let the incomplete ones finish
                        } else if (hasErrorFiles && !hasCompletedFiles) {
                            newErrors[req.id] = `Please fix the errors with ${req.name} or upload a new file`;
                        }
                    }
                }
            }
        });

        // Check selfie
        if (!selfieDataUrl) {
            newErrors.selfie = 'Selfie photo is required';
        }

        // Check signature
        if (!signatureDataUrl) {
            newErrors.signature = 'Digital signature is required';
        } else if (signatureRef.current && signatureRef.current.isEmpty()) {
            newErrors.signature = 'Please provide a valid signature';
        }

        // Check if any files are still being processed
        const hasProcessingFiles = Object.values(uploadedFiles).some(
            files => files && files.some(file => file.status === 'uploading' || file.status === 'validating')
        );

        if (hasProcessingFiles) {
            newErrors.general = 'Please wait for all files to finish uploading';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = () => {
        if (!validateDocuments()) {
            return;
        }

        // Prepare document data for submission with enhanced metadata
        const documentsData = {
            uploadedDocuments: uploadedFiles,
            selfie: selfieDataUrl,
            signature: signatureDataUrl,
            uploadedAt: new Date().toISOString(),
            // Add document references in a structured format for easier access
            documentReferences: Object.entries(uploadedFiles).reduce((acc, [docType, files]) => {
                // Only include completed files
                const completedFiles = files.filter(file => file.status === 'completed');
                if (completedFiles.length > 0) {
                    acc[docType] = completedFiles.map(file => ({
                        id: file.id,
                        path: file.path || '',
                        name: file.name,
                        type: file.mimeType || file.file.type,
                        size: file.size,
                        uploadedAt: file.uploadedAt || new Date().toISOString(),
                        securityHash: file.securityHash || '',
                        metadata: {
                            dimensions: file.dimensions,
                            pageCount: file.pageCount
                        }
                    }));
                }
                return acc;
            }, {} as Record<string, any[]>),
            // Add document validation summary
            validationSummary: {
                allDocumentsValid: Object.values(uploadedFiles).every(files =>
                    files.every(file => file.status === 'completed')
                ),
                totalDocuments: Object.values(uploadedFiles).reduce(
                    (sum, files) => sum + files.length, 0
                ),
                completedDocuments: Object.values(uploadedFiles).reduce(
                    (sum, files) => sum + files.filter(file => file.status === 'completed').length, 0
                ),
                documentTypes: Object.keys(uploadedFiles)
            }
        };

        onNext({ ...data, documents: documentsData });
    };

    // Create dropzone for each document type
    const createDropzone = (requirement: DocumentRequirement) => {
        const { getRootProps, getInputProps, isDragActive } = useDropzone({
            onDrop: (accepted, rejected) => onDrop(accepted, rejected, requirement.id),
            accept: requirement.acceptedTypes.reduce((acc, type) => ({ ...acc, [type]: [] }), {}),
            maxSize: requirement.maxSize * 1024 * 1024,
            multiple: true
        });

        const files = uploadedFiles[requirement.id] || [];

        return (
            <Card key={requirement.id} className="p-6">
                <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                        {requirement.icon}
                    </div>
                    <div>
                        <h3 className="font-semibold">{requirement.name}</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {requirement.description}
                        </p>
                        {requirement.required && (
                            <span className="text-xs text-red-600">Required</span>
                        )}
                    </div>
                </div>

                {/* National ID Card Verification (only for national_id documents) */}
                {requirement.id === 'national_id' && !idVerificationData && (
                    <div className="mb-4">
                        <IDCardVerifier
                            id="national_id_verifier"
                            label="Verify National ID Card"
                            required={true}
                            onVerificationComplete={(data) => {
                                setIdVerificationData(data);
                                setNationalIdNumber(data.idNumber);
                                setNationalIdValidated(true);
                                setErrors(prev => ({ ...prev, national_id_input: '' }));
                            }}
                            onVerificationFailed={(error) => {
                                setErrors(prev => ({ ...prev, national_id_input: error }));
                            }}
                            error={errors.national_id_input}
                        />
                    </div>
                )}

                {/* Show verification success for national_id */}
                {requirement.id === 'national_id' && idVerificationData && (
                    <div className="mb-4 p-4 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg">
                        <div className="flex items-start gap-3">
                            <CheckCircle2 className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                    ID Card Verified Successfully
                                </p>
                                <div className="mt-2 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span className="text-gray-600 dark:text-gray-400">ID Number:</span>
                                        <p className="font-medium text-green-900 dark:text-green-100">{idVerificationData.idNumber}</p>
                                    </div>
                                    <div>
                                        <span className="text-gray-600 dark:text-gray-400">Name:</span>
                                        <p className="font-medium text-green-900 dark:text-green-100">
                                            {idVerificationData.firstName} {idVerificationData.lastName}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-gray-600 dark:text-gray-400">Card Type:</span>
                                        <p className="font-medium text-green-900 dark:text-green-100">
                                            {idVerificationData.cardType === 'metal' ? 'Metal Card' : 'Plastic Card'}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-gray-600 dark:text-gray-400">Confidence:</span>
                                        <p className="font-medium text-green-900 dark:text-green-100">
                                            {(idVerificationData.confidence * 100).toFixed(0)}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Dropzone / Camera / Preview */}
                {capturedDocumentPhotos[requirement.id] ? (
                    /* Photo Preview with Retake/Use options */
                    <div className="space-y-4">
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 text-center">
                                Photo captured successfully! Review and confirm:
                            </p>
                            <img
                                src={capturedDocumentPhotos[requirement.id]}
                                alt={`Captured ${requirement.name}`}
                                className="w-full max-w-md mx-auto rounded-lg border-2 border-emerald-500"
                            />
                        </div>
                        <div className="flex justify-center gap-3">
                            <Button
                                onClick={() => retakeDocumentPhoto(requirement.id)}
                                variant="outline"
                                type="button"
                            >
                                <Camera className="h-5 w-5 mr-2" />
                                Retake Photo
                            </Button>
                            <Button
                                onClick={() => uploadCapturedDocumentPhoto(requirement.id)}
                                className="bg-emerald-600 hover:bg-emerald-700"
                                type="button"
                            >
                                <Check className="h-5 w-5 mr-2" />
                                Use This Photo
                            </Button>
                        </div>
                    </div>
                ) : activeDocumentCamera !== requirement.id ? (
                    <div>
                            <div
                                {...getRootProps()}
                                className={`border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors ${
                                    isDragActive
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'
                                }`}
                            >
                                <input {...getInputProps()} />
                                <Upload className="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Drag & drop files here, or click to select from phone gallery
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    Max size: {requirement.maxSize}MB | Formats: JPG, PNG, PDF
                                </p>
                            </div>

                            {/* Camera capture option for ALL documents */}
                            <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p className="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                                    Or capture with camera
                                </p>
                                <Button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        startDocumentCamera(requirement.id);
                                    }}
                                    variant="outline"
                                    className="w-full"
                                    type="button"
                                >
                                    <Camera className="h-4 w-4 mr-2" />
                                    Take Photo
                                </Button>
                            </div>
                        </div>
                ) : (
                    /* Camera View */
                    <div className="space-y-4">
                        <div className="relative bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                            <video
                                ref={idVideoRef}
                                autoPlay
                                playsInline
                                muted
                                className="w-full rounded-lg"
                                style={{ maxHeight: '500px' }}
                            />
                            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div className="border-2 border-emerald-500 rounded-lg"
                                     style={{
                                         width: '85%',
                                         height: '60%',
                                         boxShadow: '0 0 0 9999px rgba(0,0,0,0.3)'
                                     }}>
                                </div>
                            </div>
                        </div>
                        <div className="text-center space-y-2">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Position your document within the frame
                            </p>
                            <div className="flex justify-center gap-3">
                                <Button
                                    onClick={() => captureDocumentPhoto(requirement.id)}
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                    type="button"
                                >
                                    <Camera className="h-5 w-5 mr-2" />
                                    Capture Photo
                                </Button>
                                <Button
                                    onClick={stopDocumentCamera}
                                    variant="outline"
                                    type="button"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                        <canvas ref={idCanvasRef} style={{ display: 'none' }} />
                    </div>
                )}

                {/* Uploaded files */}
                {files.length > 0 && (
                    <div className="mt-4 space-y-3">
                        {files.map((file, index) => (
                            <div key={file.id} className="bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden">
                                <div className="flex items-center justify-between p-3">
                                    <div className="flex items-center gap-3">
                                        {file.status === 'error' ? (
                                            <div className="p-1 bg-red-100 dark:bg-red-900/30 rounded-full">
                                                <AlertCircle className="h-4 w-4 text-red-600 dark:text-red-400" />
                                            </div>
                                        ) : file.status === 'completed' ? (
                                            <div className="p-1 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                <FileCheck className="h-4 w-4 text-green-600 dark:text-green-400" />
                                            </div>
                                        ) : file.status === 'validating' ? (
                                            <div className="p-1 bg-yellow-100 dark:bg-yellow-900/30 rounded-full">
                                                <div className="h-4 w-4 text-yellow-600 dark:text-yellow-400 animate-pulse">
                                                    <FileWarning className="h-4 w-4" />
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="p-1 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                                <FileIcon className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                        )}
                                        <div>
                                            <p className="text-sm font-medium">{file.file.name}</p>
                                            <div className="flex items-center gap-2">
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {(file.file.size / 1024 / 1024).toFixed(2)} MB
                                                </p>
                                                {file.pageCount && (
                                                    <span className="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">
                                                        {file.pageCount} {file.pageCount === 1 ? 'page' : 'pages'}
                                                    </span>
                                                )}
                                                {file.dimensions && (
                                                    <span className="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">
                                                        {file.dimensions.width}×{file.dimensions.height}
                                                    </span>
                                                )}
                                                {file.securityHash && (
                                                    <span className="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded"
                                                          title={`Security hash: ${file.securityHash}`}>
                                                        <span className="flex items-center gap-1">
                                                            <Info className="h-3 w-3" />
                                                            Verified
                                                        </span>
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {file.status === 'validating' && (
                                            <span className="text-xs text-yellow-600 dark:text-yellow-400 animate-pulse">
                                                Validating...
                                            </span>
                                        )}
                                        {file.status === 'uploading' && (
                                            <span className="text-xs text-blue-600 dark:text-blue-400">
                                                {file.progress}%
                                            </span>
                                        )}
                                        {file.status === 'completed' && file.path && (
                                            <span className="text-xs text-green-600 dark:text-green-400">
                                                Stored
                                            </span>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => window.open(file.preview, '_blank')}
                                            disabled={file.status === 'validating'}
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => removeFile(requirement.id, file.id)}
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Progress indicators */}
                                {file.status === 'validating' && (
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 h-1">
                                        <div className="bg-yellow-500 h-1 w-full animate-pulse" />
                                    </div>
                                )}

                                {file.status === 'uploading' && (
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 h-1">
                                        <div
                                            className="bg-blue-600 h-1 transition-all duration-300"
                                            style={{ width: `${file.progress || 0}%` }}
                                        />
                                    </div>
                                )}

                                {/* Validation errors */}
                                {file.status === 'error' && file.validationErrors && file.validationErrors.length > 0 && (
                                    <div className="p-3 pt-0 text-xs text-red-600 dark:text-red-400">
                                        <ul className="list-disc pl-4 space-y-1">
                                            {file.validationErrors.map((error, i) => (
                                                <li key={i}>{error}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {/* File path for completed uploads */}
                                {file.status === 'completed' && file.path && (
                                    <div className="px-3 pb-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span className="font-medium">Path:</span> {file.path}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {/* Error message */}
                {errors[requirement.id] && (
                    <div className="mt-3 flex items-center gap-2 text-red-600 text-sm">
                        <AlertCircle className="h-4 w-4" />
                        {errors[requirement.id]}
                    </div>
                )}
            </Card>
        );
    };

    // Selfie file upload component
    const SelfieFileUpload = ({ onPhotoSelected, onCancel }: {
        onPhotoSelected: (dataUrl: string) => void;
        onCancel: () => void;
    }) => {
        const { getRootProps, getInputProps, isDragActive } = useDropzone({
            onDrop: (acceptedFiles) => {
                if (acceptedFiles.length > 0) {
                    const file = acceptedFiles[0];
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (e.target?.result) {
                            onPhotoSelected(e.target.result as string);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            },
            accept: {
                'image/jpeg': ['.jpg', '.jpeg'],
                'image/png': ['.png']
            },
            maxSize: 5 * 1024 * 1024, // 5MB
            multiple: false
        });

        return (
            <Card className="p-6">
                <div className="text-center space-y-4">
                    <h4 className="font-semibold">Upload Selfie Photo</h4>
                    <div
                        {...getRootProps()}
                        className={`border-2 border-dashed rounded-lg p-8 cursor-pointer transition-colors ${
                            isDragActive
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'
                        }`}
                    >
                        <input {...getInputProps()} />
                        <Upload className="h-12 w-12 mx-auto text-gray-400 mb-4" />
                        <p className="text-gray-600 dark:text-gray-400">
                            Drag & drop a selfie photo here, or click to select from phone gallery
                        </p>
                        <p className="text-xs text-gray-500 mt-2">
                            JPG or PNG, max 5MB
                        </p>
                    </div>

                    <div className="flex justify-center gap-3">
                        <Button onClick={onCancel} variant="outline">
                            Cancel
                        </Button>
                    </div>

                    <div className="text-xs text-gray-500">
                        <p>• Ensure your face is clearly visible</p>
                        <p>• Good lighting and focus</p>
                        <p>• No sunglasses or hats</p>
                    </div>
                </div>
            </Card>
        );
    };

    return (
        <div className="max-w-4xl mx-auto space-y-8">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Document Upload & Verification</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Please upload the required documents, take a selfie, and provide your digital signature
                </p>
            </div>

            {/* Required Documents */}
            <div className="space-y-6">
                <h3 className="text-lg font-semibold">Required Documents</h3>
                {documentRequirements.map(requirement => createDropzone(requirement))}
            </div>

            {/* Selfie Capture */}
            <Card className="p-6">
                <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                        <Camera className="h-6 w-6" />
                    </div>
                    <div>
                        <h3 className="font-semibold">Selfie Photo</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Take a clear selfie photo
                        </p>
                        <span className="text-xs text-red-600">Required</span>
                    </div>
                </div>

                {!selfieDataUrl && !showCamera && (
                    <div className="text-center space-y-4">
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-8">
                            <Camera className="h-16 w-16 mx-auto text-gray-400 mb-4" />
                            <p className="text-gray-600 dark:text-gray-400 mb-4">
                                We need to verify your identity with a selfie photo
                            </p>
                            <Button onClick={startCamera} className="bg-emerald-600 hover:bg-emerald-700">
                                <Camera className="h-5 w-5 mr-2" />
                                Start Camera
                            </Button>
                        </div>
                        <div className="text-xs text-gray-500">
                            <p>• Make sure you're in a well-lit area</p>
                            <p>• Look directly at the camera</p>
                            <p>• Remove sunglasses or hats</p>
                        </div>
                    </div>
                )}

                {showCamera && (
                    <div className="space-y-4">
                        <div className="relative">
                            <video
                                ref={videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="w-full max-w-md mx-auto rounded-lg bg-gray-100 dark:bg-gray-800"
                                style={{ maxHeight: '400px' }}
                            />
                            <div className="absolute inset-0 flex items-center justify-center text-gray-500 pointer-events-none">
                                <div className="text-center">
                                    <Camera className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                    <p className="text-sm">Position your face in the frame</p>
                                </div>
                            </div>
                        </div>
                        <div className="flex justify-center gap-3">
                            <Button onClick={capturePhoto} className="bg-emerald-600 hover:bg-emerald-700">
                                <Camera className="h-5 w-5 mr-2" />
                                Capture Photo
                            </Button>
                            <Button onClick={stopCamera} variant="outline">
                                Cancel
                            </Button>
                        </div>
                        <p className="text-center text-sm text-gray-600 dark:text-gray-400">
                            Make sure your face is clearly visible and well-lit
                        </p>
                    </div>
                )}

                {selfieDataUrl && (
                    <div className="space-y-4">
                        <img
                            src={selfieDataUrl}
                            alt="Selfie"
                            className="w-48 h-48 object-cover rounded-lg mx-auto"
                        />
                        <div className="flex justify-center gap-3">
                            <Button onClick={retakeSelfie} variant="outline">
                                <Camera className="h-5 w-5 mr-2" />
                                Retake
                            </Button>
                        </div>
                    </div>
                )}

                <canvas ref={canvasRef} style={{ display: 'none' }} />

                {errors.selfie && (
                    <div className="mt-3 flex items-center gap-2 text-red-600 text-sm">
                        <AlertCircle className="h-4 w-4" />
                        {errors.selfie}
                    </div>
                )}
            </Card>

            {/* Digital Signature */}
            <Card className="p-6">
                <div className="flex items-center gap-3 mb-4">
                    <div className="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                        <Edit3 className="h-6 w-6" />
                    </div>
                    <div>
                        <h3 className="font-semibold">Digital Signature</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Sign with your finger or mouse to confirm your application
                        </p>
                        <span className="text-xs text-red-600">Required</span>
                    </div>
                </div>

                <div className="border border-gray-300 dark:border-gray-600 rounded-lg">
                    <SignatureCanvas
                        ref={signatureRef}
                        canvasProps={{
                            width: 500,
                            height: 200,
                            className: 'signature-canvas w-full'
                        }}
                        {...({ onEnd: saveSignature } as Record<string, unknown>)}
                    />
                </div>

                <div className="flex justify-between mt-3">
                    <Button onClick={clearSignature} variant="outline" size="sm">
                        Clear
                    </Button>
                    <p className="text-xs text-gray-500">
                        Sign above to complete your application
                    </p>
                </div>

                {errors.signature && (
                    <div className="mt-3 flex items-center gap-2 text-red-600 text-sm">
                        <AlertCircle className="h-4 w-4" />
                        {errors.signature}
                    </div>
                )}
            </Card>

            {/* Navigation */}
            <div className="flex justify-between pt-6">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onBack}
                    disabled={loading}
                    className="flex items-center gap-2"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>

                <Button
                    onClick={handleSubmit}
                    disabled={loading}
                    className="bg-emerald-600 hover:bg-emerald-700 px-8"
                >
                    {loading ? 'Submitting...' : 'Complete Application'}
                </Button>
            </div>
        </div>
    );
};

export default DocumentUploadStep;
