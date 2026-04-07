import React, { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Upload, FileText, CheckCircle, AlertCircle, Loader2 } from 'lucide-react';
import { useDropzone } from 'react-dropzone';

interface ApplicationResubmissionProps {
    sessionId: string;
    type: 'reupload' | 'employment_proof' | 'deposit_payment';
    unclearDocuments?: string[];
    onSuccess: (message: string) => void;
}

const ApplicationResubmission: React.FC<ApplicationResubmissionProps> = ({ 
    sessionId, 
    type, 
    unclearDocuments = [],
    onSuccess 
}) => {
    const [files, setFiles] = useState<Record<string, File>>({});
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');

    const docLabels: Record<string, string> = {
        'id': 'National ID Card',
        'payslip': 'Latest Payslip',
        'photo': 'Passport Photo / Selfie',
        'employment_proof': 'Confirmation of Employment Letter',
        'deposit_receipt': 'Proof of Deposit Payment (Receipt / Screenshot)'
    };

    const handleUpload = async () => {
        const requiredDocs = type === 'employment_proof' ? ['employment_proof']
            : type === 'deposit_payment' ? ['deposit_receipt']
            : unclearDocuments;
        
        const missing = requiredDocs.filter(d => !files[d]);
        if (missing.length > 0) {
            setError(`Please upload: ${missing.map(m => docLabels[m]).join(', ')}`);
            return;
        }

        setUploading(true);
        setError('');

        try {
            const formData = new FormData();
            formData.append('sessionId', sessionId);
            formData.append('type', type);
            
            Object.entries(files).forEach(([key, file]) => {
                formData.append(`documents[${key}]`, file);
            });

            const response = await fetch('/api/application/resubmit', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                onSuccess(result.message || 'Documents submitted successfully!');
            } else {
                setError(result.message || 'Failed to upload documents. Please try again.');
            }
        } catch (err) {
            setError('An error occurred during upload. Please check your connection.');
        } finally {
            setUploading(false);
        }
    };

    const renderDropzone = (docType: string) => {
        const { getRootProps, getInputProps, isDragActive } = useDropzone({
            onDrop: (accepted) => {
                if (accepted.length > 0) {
                    setFiles(prev => ({ ...prev, [docType]: accepted[0] }));
                    setError('');
                }
            },
            accept: {
                'image/jpeg': ['.jpg', '.jpeg'],
                'image/png': ['.png'],
                'application/pdf': ['.pdf']
            },
            multiple: false
        });

        const file = files[docType];

        return (
            <div key={docType} className="mb-4">
                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                    {docLabels[docType] || docType}
                </label>
                <div
                    {...getRootProps()}
                    className={`border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors ${
                        file ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/10' : 
                        isDragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                    }`}
                >
                    <input {...getInputProps()} />
                    {file ? (
                        <div className="flex items-center justify-center gap-2 text-emerald-600">
                            <CheckCircle className="h-5 w-5" />
                            <span className="text-sm font-medium truncate max-w-[200px]">{file.name}</span>
                        </div>
                    ) : (
                        <div className="text-gray-500">
                            <Upload className="h-6 w-6 mx-auto mb-1 opacity-50" />
                            <p className="text-xs">Click or drag to upload {docLabels[docType]}</p>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    return (
        <Card className="p-6 border-2 border-blue-100 dark:border-blue-900 shadow-sm mt-6">
            <div className="flex items-center gap-2 mb-4 text-blue-700 dark:text-blue-400">
                <FileText className="h-5 w-5" />
                <h3 className="font-bold">
                    {type === 'employment_proof' ? 'Upload Confirmation of Employment'
                     : type === 'deposit_payment' ? 'Upload Proof of Deposit Payment'
                     : 'Re-upload Unclear Documents'}
                </h3>
            </div>

            <div className="grid gap-2 sm:grid-cols-2">
                {type === 'employment_proof' ? renderDropzone('employment_proof')
                 : type === 'deposit_payment' ? renderDropzone('deposit_receipt')
                 : unclearDocuments.map(renderDropzone)}
            </div>

            {error && (
                <div className="flex items-center gap-2 text-red-600 text-sm mt-2 mb-4">
                    <AlertCircle className="h-4 w-4" />
                    {error}
                </div>
            )}

            <Button 
                onClick={handleUpload} 
                disabled={uploading}
                className="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold h-11"
            >
                {uploading ? (
                    <>
                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                        Uploading...
                    </>
                ) : (
                    'Submit Documents'
                )}
            </Button>
            <p className="text-[10px] text-gray-400 mt-3 text-center italic">
                Files must be under 10MB. Formats: JPG, PNG, PDF.
            </p>
        </Card>
    );
};

export default ApplicationResubmission;
