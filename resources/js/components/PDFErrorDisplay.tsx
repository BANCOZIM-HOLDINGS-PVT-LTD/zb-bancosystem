import React from 'react';
import { Alert, AlertTitle } from './ui/alert';
import { Button } from './ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from './ui/card';
import { AlertCircle, AlertTriangle, RefreshCw, X } from 'lucide-react';

interface PDFErrorProps {
  error: {
    error: string;
    code: string;
    details?: Record<string, any>;
    message?: string;
  };
  onRetry?: () => void;
  onClose?: () => void;
  showDetails?: boolean;
}

/**
 * Enhanced component for displaying PDF generation errors in a user-friendly way
 * with improved error categorization and actionable feedback
 */
const PDFErrorDisplay: React.FC<PDFErrorProps> = ({ 
  error, 
  onRetry, 
  onClose,
  showDetails = false 
}) => {
  // Map error codes to user-friendly messages with suggested actions
  const getErrorInfo = (code: string): { message: string; action: string; severity: 'error' | 'warning' } => {
    const errorMap: Record<string, { message: string; action: string; severity: 'error' | 'warning' }> = {
      // Data validation errors - Warning severity
      'PDF_INCOMPLETE_DATA': {
        message: 'Your application is missing some required information.',
        action: 'Please complete all required fields and try again.',
        severity: 'warning'
      },
      'APPLICATION_INCOMPLETE': {
        message: 'Your application is not complete.',
        action: 'Please finish all steps before generating a PDF.',
        severity: 'warning'
      },
      'VALIDATION_FAILED': {
        message: 'Some information in your application is invalid.',
        action: 'Please check the form and try again.',
        severity: 'warning'
      },
      
      // Resource errors - Error severity
      'APPLICATION_NOT_FOUND': {
        message: 'We couldn\'t find your application.',
        action: 'It may have expired or been removed. Please start a new application.',
        severity: 'error'
      },
      
      // System errors - Error severity
      'PDF_GENERATION_FAILED': {
        message: 'We encountered a problem while creating your PDF.',
        action: 'Our team has been notified of this issue. Please try again later.',
        severity: 'error'
      },
      'PDF_STORAGE_FAILED': {
        message: 'We couldn\'t save your generated PDF.',
        action: 'Please try again or contact support if the problem persists.',
        severity: 'error'
      },
      'UNEXPECTED_ERROR': {
        message: 'An unexpected error occurred while processing your PDF.',
        action: 'Please try again later or contact support if the problem persists.',
        severity: 'error'
      }
    };

    return errorMap[code] || {
      message: 'An unexpected error occurred while processing your PDF.',
      action: 'Please try again later or contact support if the problem persists.',
      severity: 'error'
    };
  };

  // Get specific field errors if available
  const getFieldErrors = () => {
    if (error.details?.errors) {
      const entries = Object.entries(error.details.errors as Record<string, string | string[]>);
      return entries.map(([field, messages]) => {
        const messageText = Array.isArray(messages) ? messages.join(', ') : String(messages);
        return (
          <li key={field}>
            <strong>{field}:</strong> {messageText}
          </li>
        );
      });
    }
    return null;
  };

  // Get missing fields if available
  const getMissingFields = () => {
    const missingFieldsData = error.details?.missing_fields;
    if (Array.isArray(missingFieldsData)) {
      return (
        <div className="mt-4">
          <p className="font-semibold">Missing required information:</p>
          <ul className="list-disc pl-5 mt-2">
            {missingFieldsData.map((field, index) => (
              <li key={index}>{field}</li>
            ))}
          </ul>
        </div>
      );
    }
    return null;
  };

  // Get technical details for debugging
  const getTechnicalDetails = () => {
    if (!showDetails) return null;
    
    return (
      <div className="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded text-xs font-mono overflow-auto">
        <p className="font-semibold mb-1">Technical Details:</p>
        <p>Error Code: {error.code}</p>
        <p>Message: {error.message || error.error}</p>
        {error.details?.session_id && <p>Session ID: {error.details.session_id}</p>}
        {error.details?.timestamp && <p>Timestamp: {error.details.timestamp}</p>}
        {error.details?.request_id && <p>Request ID: {error.details.request_id}</p>}
      </div>
    );
  };

  const errorInfo = getErrorInfo(error.code);
  const fieldErrors = getFieldErrors();
  const missingFields = getMissingFields();

  return (
    <Card className="w-full max-w-md mx-auto border-l-4 border-l-red-500">
      <CardHeader className={errorInfo.severity === 'error' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-amber-50 dark:bg-amber-900/20'}>
        <div className="flex items-center gap-2">
          {errorInfo.severity === 'error' ? (
            <AlertCircle className="h-5 w-5 text-red-500" />
          ) : (
            <AlertTriangle className="h-5 w-5 text-amber-500" />
          )}
          <CardTitle>
            {errorInfo.severity === 'error' ? 'Error Generating PDF' : 'Cannot Generate PDF'}
          </CardTitle>
        </div>
        <CardDescription>
          {error.code}
        </CardDescription>
      </CardHeader>
      <CardContent className="pt-4">
        <Alert variant={errorInfo.severity === 'error' ? 'destructive' : 'default'}>
          <AlertTitle>{error.error || 'Error'}</AlertTitle>
          <div className="mt-2">
            <p>{errorInfo.message}</p>
            <p className="mt-1 font-medium">{errorInfo.action}</p>
          </div>
        </Alert>
        
        {fieldErrors && (
          <div className="mt-4">
            <p className="font-semibold">Please fix the following issues:</p>
            <ul className="list-disc pl-5 mt-2">
              {fieldErrors}
            </ul>
          </div>
        )}
        
        {missingFields}
        {getTechnicalDetails()}
      </CardContent>
      <CardFooter className="flex justify-end gap-2 bg-gray-50 dark:bg-gray-800/50">
        {onClose && (
          <Button variant="outline" onClick={onClose} className="flex items-center gap-1">
            <X className="h-4 w-4" />
            Close
          </Button>
        )}
        {onRetry && (
          <Button onClick={onRetry} className="flex items-center gap-1">
            <RefreshCw className="h-4 w-4" />
            Try Again
          </Button>
        )}
      </CardFooter>
    </Card>
  );
};

export default PDFErrorDisplay;
