import React, { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

interface AddressData {
  type: 'urban' | 'rural' | '';
  houseNumber?: string;
  streetName?: string;
  suburb?: string;
  city?: string;
  province?: string;
  district?: string;
  ward?: string;
  village?: string;
}

interface AddressInputProps {
  value: AddressData;
  onChange: (value: AddressData) => void;
  error?: string;
  required?: boolean;
  className?: string;
  label?: string;
}

// Zimbabwe provinces and their districts
const ZIMBABWE_LOCATIONS = {
  'Harare': {
    districts: ['Harare Urban', 'Epworth', 'Norton', 'Chitungwiza'],
    wards: Array.from({length: 46}, (_, i) => `Ward ${i + 1}`)
  },
  'Bulawayo': {
    districts: ['Bulawayo Urban'],
    wards: Array.from({length: 29}, (_, i) => `Ward ${i + 1}`)
  },
  'Manicaland': {
    districts: ['Mutare', 'Rusape', 'Chipinge', 'Makoni', 'Nyanga', 'Buhera', 'Chimanimani'],
    wards: Array.from({length: 40}, (_, i) => `Ward ${i + 1}`)
  },
  'Mashonaland Central': {
    districts: ['Bindura', 'Guruve', 'Centenary', 'Mount Darwin', 'Rushinga', 'Shamva', 'Mazowe'],
    wards: Array.from({length: 35}, (_, i) => `Ward ${i + 1}`)
  },
  'Mashonaland East': {
    districts: ['Marondera', 'Wedza', 'Goromonzi', 'Seke', 'Mudzi', 'Mutoko', 'Uzumba-Maramba-Pfungwe'],
    wards: Array.from({length: 40}, (_, i) => `Ward ${i + 1}`)
  },
  'Mashonaland West': {
    districts: ['Chinhoyi', 'Kadoma', 'Kariba', 'Makonde', 'Zvimba', 'Chegutu', 'Mhondoro-Ngezi'],
    wards: Array.from({length: 35}, (_, i) => `Ward ${i + 1}`)
  },
  'Masvingo': {
    districts: ['Masvingo Urban', 'Chivi', 'Gutu', 'Bikita', 'Zaka', 'Chiredzi', 'Mwenezi'],
    wards: Array.from({length: 35}, (_, i) => `Ward ${i + 1}`)
  },
  'Matabeleland North': {
    districts: ['Victoria Falls', 'Hwange', 'Binga', 'Lupane', 'Nkayi', 'Tsholotsho'],
    wards: Array.from({length: 30}, (_, i) => `Ward ${i + 1}`)
  },
  'Matabeleland South': {
    districts: ['Gwanda', 'Beitbridge', 'Insiza', 'Matobo', 'Umzingwane', 'Bulilima'],
    wards: Array.from({length: 30}, (_, i) => `Ward ${i + 1}`)
  },
  'Midlands': {
    districts: ['Gweru', 'Kwekwe', 'Redcliff', 'Shurugwi', 'Zvishavane', 'Chirumhanzu', 'Gokwe North', 'Gokwe South'],
    wards: Array.from({length: 40}, (_, i) => `Ward ${i + 1}`)
  }
};

const AddressInput: React.FC<AddressInputProps> = ({
  value,
  onChange,
  error,
  required = false,
  className = '',
  label = 'Address'
}) => {
  const hasError = !!error;

  const handleFieldChange = (field: keyof AddressData, newValue: string) => {
    const updatedValue = { ...value, [field]: newValue };
    
    // Clear dependent fields when parent changes
    if (field === 'type') {
      updatedValue.houseNumber = '';
      updatedValue.streetName = '';
      updatedValue.suburb = '';
      updatedValue.city = '';
      updatedValue.province = '';
      updatedValue.district = '';
      updatedValue.ward = '';
      updatedValue.village = '';
    } else if (field === 'province') {
      updatedValue.district = '';
      updatedValue.ward = '';
    } else if (field === 'district') {
      updatedValue.ward = '';
    }
    
    onChange(updatedValue);
  };

  const renderUrbanFields = () => (
    <>
      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <Label htmlFor="houseNumber">House Number</Label>
          <Input
            id="houseNumber"
            value={value.houseNumber || ''}
            onChange={(e) => handleFieldChange('houseNumber', e.target.value)}
            placeholder="e.g., 123"
          />
        </div>
        <div>
          <Label htmlFor="streetName">Street Name</Label>
          <Input
            id="streetName"
            value={value.streetName || ''}
            onChange={(e) => handleFieldChange('streetName', e.target.value)}
            placeholder="e.g., Main Street"
          />
        </div>
      </div>
      
      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <Label htmlFor="suburb">Suburb</Label>
          <Input
            id="suburb"
            value={value.suburb || ''}
            onChange={(e) => handleFieldChange('suburb', e.target.value)}
            placeholder="e.g., Avondale"
          />
        </div>
        <div>
          <Label htmlFor="city">City</Label>
          <Input
            id="city"
            value={value.city || ''}
            onChange={(e) => handleFieldChange('city', e.target.value)}
            placeholder="e.g., Harare"
          />
        </div>
      </div>
    </>
  );

  const renderRuralFields = () => (
    <>
      <div>
        <Label htmlFor="province">Province *</Label>
        <Select 
          value={value.province || ''} 
          onValueChange={(val) => handleFieldChange('province', val)}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select Province" />
          </SelectTrigger>
          <SelectContent>
            {Object.keys(ZIMBABWE_LOCATIONS).map((province) => (
              <SelectItem key={province} value={province}>
                {province}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {value.province && value.province !== 'Harare' && value.province !== 'Bulawayo' && (
        <>
          <div className="grid gap-4 md:grid-cols-2">
            <div>
              <Label htmlFor="district">District *</Label>
              <Select 
                value={value.district || ''} 
                onValueChange={(val) => handleFieldChange('district', val)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select District" />
                </SelectTrigger>
                <SelectContent>
                  {ZIMBABWE_LOCATIONS[value.province as keyof typeof ZIMBABWE_LOCATIONS]?.districts.map((district) => (
                    <SelectItem key={district} value={district}>
                      {district}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label htmlFor="ward">Ward *</Label>
              <Select 
                value={value.ward || ''} 
                onValueChange={(val) => handleFieldChange('ward', val)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select Ward" />
                </SelectTrigger>
                <SelectContent>
                  {ZIMBABWE_LOCATIONS[value.province as keyof typeof ZIMBABWE_LOCATIONS]?.wards.map((ward) => (
                    <SelectItem key={ward} value={ward}>
                      {ward}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          
          <div>
            <Label htmlFor="village">Village/Area</Label>
            <Input
              id="village"
              value={value.village || ''}
              onChange={(e) => handleFieldChange('village', e.target.value)}
              placeholder="e.g., Village name or area description"
            />
          </div>
        </>
      )}

      {(value.province === 'Harare' || value.province === 'Bulawayo') && (
        <p className="text-sm text-amber-600">
          For Harare and Bulawayo, please use Urban address format
        </p>
      )}
    </>
  );

  return (
    <div className={cn('space-y-4', className)}>
      <Label className="flex items-center text-base font-medium">
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </Label>
      
      <div>
        <Label htmlFor="addressType">Address Type *</Label>
        <Select 
          value={value.type || ''} 
          onValueChange={(val: 'urban' | 'rural') => handleFieldChange('type', val)}
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

      {value.type === 'urban' && renderUrbanFields()}
      {value.type === 'rural' && renderRuralFields()}

      {hasError && (
        <p className="text-red-500 text-xs mt-1">{error}</p>
      )}
    </div>
  );
};

export default AddressInput;