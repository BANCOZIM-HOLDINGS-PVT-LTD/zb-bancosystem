import { Head } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import ReferenceCodeEntry from '@/components/ReferenceCodeEntry';

export default function ReferenceCodeLookup() {
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [initialCode, setInitialCode] = useState('');

  // Check for code in URL parameters
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    if (code) {
      setInitialCode(code);
    }
  }, []);

  const handleReferenceCodeSubmit = async (code: string) => {
    setIsProcessing(true);
    setError('');
    setSuccessMessage('');

    try {
      // Sanitize the code by removing spaces and special characters (keep only alphanumeric)
      const sanitizedCode = code.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

      // Validate code format (must be alphanumeric, minimum 5 characters)
      if (!sanitizedCode || sanitizedCode.length < 5) {
        setError('Please enter a valid National ID number (minimum 5 characters)');
        setIsProcessing(false);
        return;
      }

      // First validate the reference code
      const validateResponse = await fetch('/api/reference-code/validate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ code: sanitizedCode }),
      });

      const validateData = await validateResponse.json();

      if (!validateData.success) {
        setError(validateData.message || 'Invalid National ID or reference code. Please check and try again.');
        setIsProcessing(false);
        return;
      }

      // If valid, get the application state
      const stateResponse = await fetch('/api/reference-code/state', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ code: sanitizedCode }),
      });

      const stateData = await stateResponse.json();

      if (!stateData.success) {
        setError(stateData.message || 'Could not retrieve application state. Please try again later.');
        setIsProcessing(false);
        return;
      }

      // Determine where to redirect based on the application state
      const { session_id, current_step, status } = stateData.data;

      // If the application has a status, redirect to the status page
      if (status && status !== 'pending') {
        setSuccessMessage(`Application found with status: ${status.toUpperCase()}. Redirecting to status page...`);
        setTimeout(() => {
          router.visit(`/application/status?ref=${sanitizedCode}`);
        }, 1500);
        return;
      }

      // If the application is in progress, redirect to resume
      if (current_step) {
        setSuccessMessage(`Application found! You can continue from step: ${current_step}. Redirecting...`);
        setTimeout(() => {
          router.visit(`/application/resume/${session_id}?code=${sanitizedCode}`);
        }, 1500);
        return;
      }

      // Fallback
      setError('Could not determine application state. Please contact support for assistance.');
    } catch (err) {
      console.error('Error processing reference code:', err);
      setError('An error occurred while processing your request. Please try again or contact support.');
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <>
      <Head title="Reference Code Lookup" />
      <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <div className="max-w-4xl mx-auto px-4 py-8">
          {/* Header */}
          <div className="mb-8">
            <Link 
              href="/"
              className="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mb-4"
            >
              <ArrowLeft className="h-4 w-4" />
              Back to Home
            </Link>
            <h1 className="text-3xl font-semibold text-gray-900 dark:text-gray-100">
              Application Lookup
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mt-2">
              Enter your National ID or reference code to resume your application or check its status
            </p>
          </div>

          {/* Reference Code Entry */}
          <ReferenceCodeEntry
            onSubmit={handleReferenceCodeSubmit}
            title="Enter Your National ID or Reference Code"
            description="Enter your National ID number or the reference code you received when you started your application"
            buttonText="Look Up"
            placeholder="e.g., 63-123456A12 or ABC123"
            initialValue={initialCode}
          />

          {/* Status Messages */}
          {error && (
            <div className="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <p className="text-red-600 dark:text-red-400">
                {error}
              </p>
            </div>
          )}

          {successMessage && (
            <div className="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <p className="text-green-600 dark:text-green-400">
                {successMessage}
              </p>
            </div>
          )}

          {/* Help Section */}
          <div className="mt-8 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <h2 className="text-xl font-semibold mb-4">Need Help?</h2>
            <p className="mb-4">
              If you've lost your reference code or are experiencing issues, please contact our support team:
            </p>
            <ul className="list-disc pl-5 space-y-2">
              <li>Email: support@bancozim.com</li>
              <li>Phone: +123-456-7890</li>
              <li>WhatsApp: +123-456-7890</li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
}