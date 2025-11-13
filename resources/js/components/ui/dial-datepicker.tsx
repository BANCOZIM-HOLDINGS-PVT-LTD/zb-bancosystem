import React, { useState, useEffect } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { CalendarIcon } from 'lucide-react';

interface DialDatePickerProps {
  id?: string;
  value: string; // YYYY-MM-DD format
  onChange: (value: string) => void;
  label?: string;
  required?: boolean;
  error?: string;
  minDate?: string; // YYYY-MM-DD format
  maxDate?: string; // YYYY-MM-DD format
  defaultAge?: number; // Default age in years
  className?: string;
  disabled?: boolean;
  showAgeValidation?: boolean; // Whether to show age validation warnings
}

const DialDatePicker: React.FC<DialDatePickerProps> = ({
  id,
  value,
  onChange,
  label,
  required = false,
  error,
  minDate,
  maxDate,
  defaultAge = 20, // Default to 20 years ago as per note 33
  className = '',
  disabled = false,
  showAgeValidation = false // Only show age validation when explicitly enabled
}) => {
  const [day, setDay] = useState('');
  const [month, setMonth] = useState('');
  const [year, setYear] = useState('');
  
  const hasError = !!error;
  
  // Parse value into day, month, year
  useEffect(() => {
    if (value) {
      const date = new Date(value);
      if (!isNaN(date.getTime())) {
        setDay(String(date.getDate()).padStart(2, '0'));
        setMonth(String(date.getMonth() + 1).padStart(2, '0'));
        setYear(String(date.getFullYear()));
      }
    } else if (!value && defaultAge) {
      // Set default date to defaultAge years ago (e.g., 20 years ago from today)
      const defaultDate = new Date();
      defaultDate.setFullYear(defaultDate.getFullYear() - defaultAge);
      defaultDate.setMonth(7); // August (month 7, 0-indexed)
      defaultDate.setDate(28); // 28th day
      const defaultValue = defaultDate.toISOString().split('T')[0];
      onChange(defaultValue);
    }
  }, [value, defaultAge]);
  
  // Generate options
  const generateDays = () => {
    const days = [];
    for (let i = 1; i <= 31; i++) {
      const dayStr = String(i).padStart(2, '0');
      days.push({ value: dayStr, label: dayStr });
    }
    return days;
  };
  
  const generateMonths = () => {
    const months = [
      { value: '01', label: 'January' },
      { value: '02', label: 'February' },
      { value: '03', label: 'March' },
      { value: '04', label: 'April' },
      { value: '05', label: 'May' },
      { value: '06', label: 'June' },
      { value: '07', label: 'July' },
      { value: '08', label: 'August' },
      { value: '09', label: 'September' },
      { value: '10', label: 'October' },
      { value: '11', label: 'November' },
      { value: '12', label: 'December' }
    ];
    return months;
  };
  
  const generateYears = () => {
    const currentYear = new Date().getFullYear();
    // For age verification: minimum age 18 means maximum birth year is current year - 18
    const maxBirthYear = maxDate ? new Date(maxDate).getFullYear() : (currentYear - 18); // 18+ years old
    // For very old applicants: go back to 1930s (assuming max age ~90-95)
    const minBirthYear = minDate ? new Date(minDate).getFullYear() : 1930; 
    
    const years = [];
    // Start from most recent allowed year and go backwards to oldest
    for (let i = maxBirthYear; i >= minBirthYear; i--) {
      years.push({ value: String(i), label: String(i) });
    }
    return years;
  };
  
  // Update date when components change
  const updateDate = (newDay: string, newMonth: string, newYear: string) => {
    if (newDay && newMonth && newYear) {
      // Validate the date
      const date = new Date(parseInt(newYear), parseInt(newMonth) - 1, parseInt(newDay));
      
      // Check if the date is valid
      if (date.getDate() == parseInt(newDay) && 
          date.getMonth() == parseInt(newMonth) - 1 && 
          date.getFullYear() == parseInt(newYear)) {
        
        const dateString = `${newYear}-${newMonth}-${newDay}`;
        
        // Check min/max constraints
        if (minDate && dateString < minDate) return;
        if (maxDate && dateString > maxDate) return;
        
        onChange(dateString);
      }
    }
  };
  
  const handleDayChange = (newDay: string) => {
    setDay(newDay);
    updateDate(newDay, month, year);
  };
  
  const handleMonthChange = (newMonth: string) => {
    setMonth(newMonth);
    updateDate(day, newMonth, year);
  };
  
  const handleYearChange = (newYear: string) => {
    setYear(newYear);
    updateDate(day, month, newYear);
  };
  
  // Calculate age for display
  const getAge = () => {
    if (value) {
      const birthDate = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
      }
      
      return age;
    }
    return null;
  };
  
  const age = getAge();
  
  return (
    <div className={cn('space-y-2', className)}>
      {label && (
        <Label htmlFor={id} className="flex items-center">
          <CalendarIcon className="w-4 h-4 mr-1" />
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </Label>
      )}
      
      <div className="grid grid-cols-3 gap-2">
        {/* Day */}
        <div>
          <Label className="text-xs text-gray-500">Day</Label>
          <Select 
            value={day} 
            onValueChange={handleDayChange}
            disabled={disabled}
          >
            <SelectTrigger className={cn(hasError ? 'border-red-500' : '')}>
              <SelectValue placeholder="DD" />
            </SelectTrigger>
            <SelectContent>
              {generateDays().map((d) => (
                <SelectItem key={d.value} value={d.value}>
                  {d.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        
        {/* Month */}
        <div>
          <Label className="text-xs text-gray-500">Month</Label>
          <Select 
            value={month} 
            onValueChange={handleMonthChange}
            disabled={disabled}
          >
            <SelectTrigger className={cn(hasError ? 'border-red-500' : '')}>
              <SelectValue placeholder="Month" />
            </SelectTrigger>
            <SelectContent>
              {generateMonths().map((m) => (
                <SelectItem key={m.value} value={m.value}>
                  {m.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        
        {/* Year */}
        <div>
          <Label className="text-xs text-gray-500">Year</Label>
          <Select 
            value={year} 
            onValueChange={handleYearChange}
            disabled={disabled}
          >
            <SelectTrigger className={cn(hasError ? 'border-red-500' : '')}>
              <SelectValue placeholder="YYYY" />
            </SelectTrigger>
            <SelectContent className="max-h-60">
              {generateYears().map((y) => (
                <SelectItem key={y.value} value={y.value}>
                  {y.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
      
      {/* Age display - only show when showAgeValidation is enabled */}
      {showAgeValidation && age !== null && (
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Age: {age} years
          {age < 18 && <span className="text-amber-600 ml-2">⚠️ Under 18</span>}
          {age >= 65 && <span className="text-blue-600 ml-2">📋 Senior Citizen</span>}
        </p>
      )}
      
      {/* Validation messages */}
      {hasError && (
        <p className="text-red-500 text-xs mt-1">{error}</p>
      )}
      
      {minDate && value && value < minDate && (
        <p className="text-red-500 text-xs mt-1">
          Date must be after {new Date(minDate).toLocaleDateString()}
        </p>
      )}
      
      {maxDate && value && value > maxDate && (
        <p className="text-red-500 text-xs mt-1">
          Date must be before {new Date(maxDate).toLocaleDateString()}
        </p>
      )}
      
      <p className="text-xs text-gray-500">
        Select day, month, and year separately
        {minDate && ` • Minimum: ${new Date(minDate).toLocaleDateString()}`}
        {maxDate && ` • Maximum: ${new Date(maxDate).toLocaleDateString()}`}
      </p>
    </div>
  );
};

export default DialDatePicker;