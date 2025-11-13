import React from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import PhoneInput from '@/components/ui/phone-input';
import DialDatePicker from '@/components/ui/dial-datepicker';
import AddressInput from '@/components/ui/address-input';
import EnhancedCheckbox from '@/components/ui/enhanced-checkbox';

interface FormFieldProps {
  id: string;
  label: string;
  type?: 'text' | 'email' | 'number' | 'date' | 'dial-date' | 'tel' | 'phone' | 'address' | 'checkbox' | 'select' | 'textarea';
  value?: string;
  onChange: (value: string) => void;
  error?: string;
  required?: boolean;
  placeholder?: string;
  options?: Array<{ value: string; label: string }>;
  className?: string;
  min?: number;
  max?: number;
  pattern?: string;
  name?: string;
  title?: string;
  checked?: boolean;
  rows?: number;
  autoCapitalize?: boolean;
  capitalizeCheckLetter?: boolean;
  minDate?: string;
  maxDate?: string;
  defaultAge?: number;
  showAgeValidation?: boolean;
  checkboxVariant?: 'default' | 'prominent' | 'warning';
  checkboxDescription?: string;
}

const FormField: React.FC<FormFieldProps> = ({
  id,
  label,
  type = 'text',
  value,
  checked,
  onChange,
  error,
  required = false,
  placeholder,
  options = [],
  className = '',
  min,
  max,
  pattern,
  name,
  title,
  rows = 3,
  autoCapitalize = false,
  capitalizeCheckLetter = false,
  minDate,
  maxDate,
  defaultAge = 20,
  showAgeValidation = false,
  checkboxVariant = 'default',
  checkboxDescription
}) => {
  const inputValue = value ?? '';
  const hasError = !!error;
  
  // Helper function to capitalize names
  const capitalizeWords = (str: string) => {
    return str.replace(/\b\w/g, (char) => char.toUpperCase());
  };
  
  // Helper function to capitalize ID check letter
  const capitalizeIDCheckLetter = (str: string) => {
    // Zimbabwe ID format: XX-XXXXXX X XX (the check letter is the 9th character after removing spaces/dashes)
    const cleanStr = str.replace(/[-\s]/g, '');
    if (cleanStr.length >= 9) {
      const parts = str.split(/(?=\s[A-Za-z]\s)/);
      if (parts.length > 1) {
        parts[parts.length - 1] = parts[parts.length - 1].toUpperCase();
        return parts.join('');
      }
    }
    return str;
  };
  
  const handleChange = (newValue: string) => {
    let processedValue = newValue;
    
    if (autoCapitalize) {
      processedValue = capitalizeWords(newValue);
    }
    
    if (capitalizeCheckLetter) {
      processedValue = capitalizeIDCheckLetter(newValue);
    }
    
    onChange(processedValue);
  };
  
  const renderInput = () => {
    switch (type) {
      case 'select':
        return (
          <Select value={inputValue} onValueChange={handleChange}>
            <SelectTrigger className={hasError ? 'border-red-500 focus:ring-red-500' : ''}>
              <SelectValue placeholder={placeholder || `Select ${label}`} />
            </SelectTrigger>
            <SelectContent>
              {options.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        );
      
      case 'textarea':
        return (
          <Textarea
            id={id}
            value={inputValue}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={placeholder}
            rows={rows}
            className={hasError ? 'border-red-500 focus:ring-red-500' : ''}
          />
        );
      
      case 'phone':
        return (
          <PhoneInput
            id={id}
            value={inputValue}
            onChange={handleChange}
            error={error}
            placeholder={placeholder}
            required={required}
            className={className}
          />
        );
      
      case 'dial-date':
        return (
          <DialDatePicker
            id={id}
            value={inputValue}
            onChange={handleChange}
            label={label}
            error={error}
            required={required}
            minDate={minDate}
            maxDate={maxDate}
            defaultAge={defaultAge}
            showAgeValidation={showAgeValidation}
            className={className}
          />
        );
      
      case 'address':
        return (
          <AddressInput
            value={JSON.parse(inputValue || '{}')}
            onChange={(addressData) => handleChange(JSON.stringify(addressData))}
            error={error}
            required={required}
            label={label}
            className={className}
          />
        );
      
      case 'checkbox':
        return (
          <EnhancedCheckbox
            id={id}
            checked={typeof checked === 'boolean' ? checked : String(inputValue).toLowerCase() === 'true'}
            onCheckedChange={(checked) => handleChange(checked.toString())}
            label={label}
            required={required}
            error={error}
            description={checkboxDescription}
            variant={checkboxVariant}
            className={className}
          />
        );
      
      default:
        return (
          <Input
            id={id}
            type={type}
            name={name}
            value={inputValue}
            onChange={(e) => handleChange(e.target.value)}
            placeholder={placeholder}
            required={required}
            min={min}
            max={max}
            pattern={pattern}
            title={title}
            className={hasError ? 'border-red-500 focus:ring-red-500' : ''}
          />
        );
    }
  };
  
  // For components that render their own labels
  if (type === 'dial-date' || type === 'address' || type === 'checkbox') {
    return (
      <div className={`space-y-1 ${className}`}>
        {renderInput()}
      </div>
    );
  }

  return (
    <div className={`space-y-1 ${className}`}>
      <Label htmlFor={id} className="flex items-center">
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </Label>
      {renderInput()}
      {hasError && (
        <p className="text-red-500 text-xs mt-1">{error}</p>
      )}
    </div>
  );
};

export default FormField;
