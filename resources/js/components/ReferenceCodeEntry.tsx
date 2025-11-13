import React, { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Search } from 'lucide-react';

interface ReferenceCodeEntryProps {
  onSubmit: (code: string) => void;
  title?: string;
  description?: string;
  buttonText?: string;
  placeholder?: string;
  initialValue?: string;
}

export default function ReferenceCodeEntry({
  onSubmit,
  title = 'Enter Reference Code',
  description = 'Enter your 6-character reference code to continue',
  buttonText = 'Submit',
  placeholder = 'e.g., ABC123',
  initialValue = '',
}: ReferenceCodeEntryProps) {
  const [code, setCode] = useState(initialValue);
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async () => {
    if (!code.trim()) {
      setError('Please enter a reference code');
      return;
    }

    if (code.trim().length !== 6) {
      setError('Reference code must be 6 characters');
      return;
    }

    setError('');
    setIsSubmitting(true);
    
    try {
      await onSubmit(code.trim().toUpperCase());
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="p-6">
      <div className="max-w-xl mx-auto">
        <Label htmlFor="reference-code" className="text-lg mb-2 block">
          {title}
        </Label>
        <p className="text-gray-600 dark:text-gray-400 mb-4">
          {description}
        </p>
        <div className="flex gap-3">
          <Input
            id="reference-code"
            placeholder={placeholder}
            value={code}
            onChange={(e) => setCode(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && handleSubmit()}
            className="text-lg"
            maxLength={6}
          />
          <Button 
            onClick={handleSubmit}
            disabled={isSubmitting}
            size="lg"
            className="bg-emerald-600 hover:bg-emerald-700"
          >
            <Search className="h-5 w-5 mr-2" />
            {isSubmitting ? 'Processing...' : buttonText}
          </Button>
        </div>
        {error && (
          <p className="text-red-600 dark:text-red-400 mt-2 text-sm">
            {error}
          </p>
        )}
      </div>
    </Card>
  );
}