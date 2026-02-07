import React from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

export interface AddressData {
  type: 'urban' | 'rural' | '';
  city?: string;           // Urban only
  wardDistrict?: string;   // Rural only  
  addressLine: string;     // Both
}

interface AddressInputProps {
  value: AddressData | string;
  onChange: (value: AddressData) => void;
  error?: string;
  required?: boolean;
  className?: string;
  label?: string;
  id?: string;
  name?: string;
}

// Zimbabwe major cities for urban selection
const ZIMBABWE_CITIES = [
  'Harare',
  'Bulawayo',
  'Chitungwiza',
  'Mutare',
  'Gweru',
  'Kwekwe',
  'Kadoma',
  'Masvingo',
  'Chinhoyi',
  'Marondera',
  'Bindura',
  'Victoria Falls',
  'Hwange',
  'Beitbridge',
  'Chiredzi',
  'Zvishavane',
  'Kariba',
  'Karoi',
  'Norton',
  'Rusape',
  'Chipinge',
  'Redcliff',
  'Chegutu',
  'Shurugwi',
  'Ruwa',
  'Epworth',
  'Gokwe',
  'Other'
];

// Helper to normalize value to AddressData
const normalizeValue = (value: AddressData | string | undefined): AddressData => {
  if (!value) {
    return { type: '', addressLine: '' };
  }

  if (typeof value === 'string') {
    // Try parsing JSON
    if (value.startsWith('{')) {
      try {
        const parsed = JSON.parse(value);
        return {
          type: parsed.type || '',
          city: parsed.city || '',
          wardDistrict: parsed.wardDistrict || '',
          addressLine: parsed.addressLine || ''
        };
      } catch {
        // If parsing fails, treat as address line
        return { type: '', addressLine: value };
      }
    }
    // Plain string - treat as address line
    return { type: '', addressLine: value };
  }

  return {
    type: value.type || '',
    city: value.city || '',
    wardDistrict: value.wardDistrict || '',
    addressLine: value.addressLine || ''
  };
};

const AddressInput: React.FC<AddressInputProps> = ({
  value,
  onChange,
  error,
  required = false,
  className = '',
  label = 'Address',
  id,
  name
}) => {
  const hasError = !!error;
  const normalizedValue = normalizeValue(value);

  const handleTypeChange = (newType: 'urban' | 'rural') => {
    onChange({
      type: newType,
      city: newType === 'urban' ? normalizedValue.city : undefined,
      wardDistrict: newType === 'rural' ? normalizedValue.wardDistrict : undefined,
      addressLine: normalizedValue.addressLine
    });
  };

  const handleFieldChange = (field: keyof AddressData, newValue: string) => {
    onChange({
      ...normalizedValue,
      [field]: newValue
    });
  };

  return (
    <div className={cn('space-y-3', className)}>
      {label && (
        <Label className="flex items-center text-base font-medium">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </Label>
      )}

      {/* Type Selection */}
      <div>
        <Label htmlFor={`${id || name}-type`} className="text-sm text-gray-600 dark:text-gray-400">
          Address Type *
        </Label>
        <Select
          value={normalizedValue.type || ''}
          onValueChange={(val: 'urban' | 'rural') => handleTypeChange(val)}
        >
          <SelectTrigger className={hasError ? 'border-red-500' : ''}>
            <SelectValue placeholder="Select address type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="urban">Urban</SelectItem>
            <SelectItem value="rural">Rural</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Urban Fields */}
      {normalizedValue.type === 'urban' && (
        <div className="grid gap-3 md:grid-cols-2">
          <div>
            <Label htmlFor={`${id || name}-city`} className="text-sm text-gray-600 dark:text-gray-400">
              City/Town *
            </Label>
            <Select
              value={normalizedValue.city || ''}
              onValueChange={(val) => handleFieldChange('city', val)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select city" />
              </SelectTrigger>
              <SelectContent>
                {ZIMBABWE_CITIES.map((city) => (
                  <SelectItem key={city} value={city}>
                    {city}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div>
            <Label htmlFor={`${id || name}-addressLine`} className="text-sm text-gray-600 dark:text-gray-400">
              Street Address *
            </Label>
            <Input
              id={`${id || name}-addressLine`}
              value={normalizedValue.addressLine || ''}
              onChange={(e) => handleFieldChange('addressLine', e.target.value)}
              placeholder="e.g., 123 Main Street, Avondale"
            />
          </div>
        </div>
      )}

      {/* Rural Fields */}
      {normalizedValue.type === 'rural' && (
        <div className="grid gap-3 md:grid-cols-2">
          <div>
            <Label htmlFor={`${id || name}-wardDistrict`} className="text-sm text-gray-600 dark:text-gray-400">
              Ward / District *
            </Label>
            <Input
              id={`${id || name}-wardDistrict`}
              value={normalizedValue.wardDistrict || ''}
              onChange={(e) => handleFieldChange('wardDistrict', e.target.value)}
              placeholder="e.g., Ward 5, Chipinge District"
            />
          </div>
          <div>
            <Label htmlFor={`${id || name}-addressLine`} className="text-sm text-gray-600 dark:text-gray-400">
              Address / Village *
            </Label>
            <Input
              id={`${id || name}-addressLine`}
              value={normalizedValue.addressLine || ''}
              onChange={(e) => handleFieldChange('addressLine', e.target.value)}
              placeholder="e.g., Village name, Chief area"
            />
          </div>
        </div>
      )}

      {hasError && (
        <p className="text-red-500 text-xs mt-1">{error}</p>
      )}
    </div>
  );
};

export default AddressInput;