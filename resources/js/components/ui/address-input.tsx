import React from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

export interface AddressData {
  type: 'urban' | 'rural' | '';
  city?: string;           // Urban - now text input (optional/merged) or removed as per request? User said "remove dropdown for city and town just go to the address line" so we can probably drop it or keep it as optional hidden. Let's keep the interface but optional.
  province?: string;       // Rural only
  district?: string;       // Rural only
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

// Zimbabwe Provinces for rural selection
const ZIMBABWE_PROVINCES = [
  'Bulawayo Metropolitan',
  'Harare Metropolitan',
  'Manicaland',
  'Mashonaland Central',
  'Mashonaland East',
  'Mashonaland West',
  'Masvingo',
  'Matabeleland North',
  'Matabeleland South',
  'Midlands'
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
          province: parsed.province || '',
          district: parsed.district || '',
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
    province: value.province || '',
    district: value.district || '',
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
      city: undefined, // Clear city
      province: newType === 'rural' ? normalizedValue.province : undefined,
      district: newType === 'rural' ? normalizedValue.district : undefined,
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

      {/* Urban Fields - Just Address Line */}
      {normalizedValue.type === 'urban' && (
        <div>
          <Label htmlFor={`${id || name}-addressLine`} className="text-sm text-gray-600 dark:text-gray-400">
            Street Address *
          </Label>
          <Input
            id={`${id || name}-addressLine`}
            value={normalizedValue.addressLine || ''}
            onChange={(e) => handleFieldChange('addressLine', e.target.value)}
            placeholder="e.g., 123 Main Street, Avondale, Harare"
          />
        </div>
      )}

      {/* Rural Fields - Province, District, Address Line */}
      {normalizedValue.type === 'rural' && (
        <div className="grid gap-3 md:grid-cols-2">
          <div className="md:col-span-2">
            <Label htmlFor={`${id || name}-province`} className="text-sm text-gray-600 dark:text-gray-400">
              Province *
            </Label>
            <Select
              value={normalizedValue.province || ''}
              onValueChange={(val) => handleFieldChange('province', val)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select Province" />
              </SelectTrigger>
              <SelectContent>
                {ZIMBABWE_PROVINCES.map((province) => (
                  <SelectItem key={province} value={province}>
                    {province}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor={`${id || name}-district`} className="text-sm text-gray-600 dark:text-gray-400">
              District *
            </Label>
            <Input
              id={`${id || name}-district`}
              value={normalizedValue.district || ''}
              onChange={(e) => handleFieldChange('district', e.target.value)}
              placeholder="e.g., Gutu"
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