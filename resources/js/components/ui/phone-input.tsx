import React, { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface PhoneInputProps {
  id?: string;
  value: string;
  onChange: (value: string) => void;
  error?: string;
  required?: boolean;
  placeholder?: string;
  className?: string;
  label?: string;
  disabled?: boolean;
}

const PhoneInput: React.FC<PhoneInputProps> = ({
  id,
  value = '',
  onChange,
  error,
  required = false,
  placeholder = '+263-7XX-XXXXXX',
  className = '',
  label,
  disabled = false
}) => {
  const [displayValue, setDisplayValue] = useState('');
  const [isValid, setIsValid] = useState(true);

  // Zimbabwe phone number validation
  const validateZimbabweNumber = (phone: string): boolean => {
    // Remove all non-digit characters
    const cleaned = phone.replace(/\D/g, '');
    
    // Check if it starts with 263 (Zimbabwe country code)
    if (!cleaned.startsWith('263')) {
      return false;
    }
    
    // Full number should be 12 digits (263 + 9 digits)
    if (cleaned.length > 0 && cleaned.length !== 12) {
      return false;
    }
    
    // Check if it has valid prefixes after 263
    if (cleaned.length >= 6) {
      const prefix = cleaned.slice(3, 6); // Get the 3-digit prefix after 263
      const validPrefixes = ['771', '772', '773', '774', '775', '776', '777', '778', '779',
                            '780', '781', '782', '783', '784', '785', '786', '787', '788', '789',
                            '712', '713', '714', '715', '716', '717', '718', '719',
                            '732', '733', '734', '735', '736', '737', '738', '739'];
      
      if (!validPrefixes.includes(prefix)) {
        return false;
      }
    }
    
    return true;
  };

  // Format phone number for display
  const formatPhoneNumber = (phone: string): string => {
    const cleaned = phone.replace(/\D/g, '');
    
    if (cleaned.length === 0) return '';
    
    // Always start with +263
    let formatted = '+';
    
    if (cleaned.length <= 3) {
      formatted += cleaned;
    } else if (cleaned.length <= 6) {
      formatted += cleaned.slice(0, 3) + '-' + cleaned.slice(3);
    } else {
      formatted += cleaned.slice(0, 3) + '-' + cleaned.slice(3, 6) + '-' + cleaned.slice(6, 12);
    }
    
    return formatted;
  };

  // Handle input change
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const input = e.target.value;
    let cleaned = input.replace(/\D/g, '');
    
    // If user is typing and doesn't have 263, add it
    if (cleaned.length > 0 && !cleaned.startsWith('263')) {
      // If they typed 0, replace with 263
      if (cleaned.startsWith('0')) {
        cleaned = '263' + cleaned.slice(1);
      } else if (cleaned.startsWith('7')) {
        // If they started with 7, prepend 263
        cleaned = '263' + cleaned;
      } else {
        // Otherwise prepend 263
        cleaned = '263' + cleaned;
      }
    }
    
    // Limit to 12 digits
    if (cleaned.length > 12) {
      cleaned = cleaned.slice(0, 12);
    }
    
    const formatted = formatPhoneNumber(cleaned);
    setDisplayValue(formatted);
    
    // Validate
    const valid = cleaned.length === 0 || validateZimbabweNumber(cleaned);
    setIsValid(valid);
    
    // Pass the cleaned number to parent
    onChange(cleaned.length > 0 ? '+' + cleaned : '');
  };

  // Update display value when value prop changes
  useEffect(() => {
    const cleaned = value.replace(/\D/g, '');
    setDisplayValue(formatPhoneNumber(cleaned));
    setIsValid(cleaned.length === 0 || validateZimbabweNumber(cleaned));
  }, [value]);

  const hasError = !!error || (!isValid && displayValue.length > 0);
  const errorMessage = error || (!isValid && displayValue.length > 0 ? 'Invalid Zimbabwe phone number' : '');

  return (
    <div className={cn('space-y-1', className)}>
      {label && (
        <Label htmlFor={id} className="flex items-center">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </Label>
      )}
      <div className="relative">
        <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
        </div>
        <Input
          id={id}
          type="tel"
          value={displayValue}
          onChange={handleChange}
          placeholder={placeholder}
          required={required}
          disabled={disabled}
          className={cn(
            'pl-10',
            hasError ? 'border-red-500 focus:ring-red-500' : '',
            className
          )}
        />
      </div>
      {hasError && (
        <p className="text-red-500 text-xs mt-1">{errorMessage}</p>
      )}
      <p className="text-gray-500 text-xs">Zimbabwe mobile numbers only (+263-7XX-XXXXXX). Valid prefixes: 771-9, 780-9 (Econet), 712-9 (Net1), 732-9 (Telecel)</p>
    </div>
  );
};

export default PhoneInput;