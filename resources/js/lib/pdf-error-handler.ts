/**
 * PDF Error Handler Utility
 * 
 * This utility provides consistent error handling for PDF operations across the application.
 * It includes functions for parsing error responses, displaying error messages, and logging errors.
 */

import { router } from '@inertiajs/react';

/**
 * Standard PDF error response structure
 */
export interface PDFErrorResponse {
  error: string;
  code: string;
  details?: Record<string, any>;
  message?: string;
}

/**
 * Parse error response from API
 * 
 * @param error The error object from a fetch or axios request
 * @returns Standardized PDFErrorResponse object
 */
export const parsePDFError = async (error: any): Promise<PDFErrorResponse> => {
  // Default error response
  const defaultError: PDFErrorResponse = {
    error: 'An unexpected error occurred',
    code: 'UNEXPECTED_ERROR',
    message: error?.message || 'Unknown error',
  };

  try {
    // If the error has a response property (axios error)
    if (error.response) {
      // Try to parse the response data
      const data = error.response.data;
      
      if (data && data.error && data.code) {
        return data as PDFErrorResponse;
      }
      
      // If response has data but not in the expected format
      return {
        error: data.message || data.error || 'API Error',
        code: data.code || 'API_ERROR',
        details: data,
        message: error.message,
      };
    }
    
    // If the error is a Response object (fetch API)
    if (error instanceof Response) {
      try {
        const data = await error.json();
        
        if (data && data.error && data.code) {
          return data as PDFErrorResponse;
        }
        
        return {
          error: data.message || data.error || `HTTP Error: ${error.status}`,
          code: data.code || 'HTTP_ERROR',
          details: data,
          message: `HTTP Error: ${error.status} ${error.statusText}`,
        };
      } catch (jsonError) {
        // If we can't parse the JSON
        return {
          error: `HTTP Error: ${error.status}`,
          code: 'HTTP_ERROR',
          message: `HTTP Error: ${error.status} ${error.statusText}`,
        };
      }
    }
    
    // If the error is a string
    if (typeof error === 'string') {
      return {
        error: error,
        code: 'ERROR',
        message: error,
      };
    }
    
    // If the error is an Error object
    if (error instanceof Error) {
      return {
        error: error.message,
        code: error.name === 'Error' ? 'UNEXPECTED_ERROR' : error.name,
        message: error.message,
        details: { stack: error.stack },
      };
    }
    
    // For any other type of error
    return defaultError;
  } catch (parsingError) {
    console.error('Error parsing PDF error:', parsingError);
    return defaultError;
  }
};

/**
 * Log PDF error to console and optionally to server
 * 
 * @param error The PDFErrorResponse object
 * @param context Additional context information
 * @param logToServer Whether to send the error to the server
 */
export const logPDFError = (
  error: PDFErrorResponse, 
  context: Record<string, any> = {}, 
  logToServer: boolean = false
): void => {
  // Log to console
  console.error('PDF Error:', error.code, error.error, {
    ...error,
    ...context,
  });
  
  // Log to server if requested
  if (logToServer) {
    try {
      fetch('/api/log/pdf-error', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          error,
          context,
          url: window.location.href,
          timestamp: new Date().toISOString(),
        }),
      }).catch(e => console.error('Failed to log error to server:', e));
    } catch (e) {
      console.error('Failed to log error to server:', e);
    }
  }
};

/**
 * Handle PDF error by displaying appropriate UI feedback
 * 
 * @param error The PDFErrorResponse object
 * @param setError Function to set error state in component
 * @param redirectOnCritical Whether to redirect on critical errors
 */
export const handlePDFError = (
  error: PDFErrorResponse,
  setError?: (error: PDFErrorResponse) => void,
  redirectOnCritical: boolean = false
): void => {
  // Log the error
  logPDFError(error);
  
  // Set error state if provided
  if (setError) {
    setError(error);
  }
  
  // For critical errors, redirect to error page if requested
  if (redirectOnCritical && ['PDF_GENERATION_FAILED', 'PDF_STORAGE_FAILED', 'UNEXPECTED_ERROR'].includes(error.code)) {
    router.visit('/error', {
      data: {
        error: error.error,
        code: error.code,
        message: error.message,
        details: error.details,
      },
    });
  }
};

/**
 * Check if an error is a PDF error
 * 
 * @param error Any error object
 * @returns True if the error is a PDF error
 */
export const isPDFError = (error: any): boolean => {
  return (
    error &&
    typeof error === 'object' &&
    'code' in error &&
    'error' in error &&
    typeof error.code === 'string' &&
    typeof error.error === 'string'
  );
};

export default {
  parsePDFError,
  logPDFError,
  handlePDFError,
  isPDFError,
};