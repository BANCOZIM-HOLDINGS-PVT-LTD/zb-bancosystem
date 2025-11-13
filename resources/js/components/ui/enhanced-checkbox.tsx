import React from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { AlertTriangle, CheckCircle2 } from 'lucide-react';

interface EnhancedCheckboxProps {
  id?: string;
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
  label: string;
  required?: boolean;
  error?: string;
  description?: string;
  variant?: 'default' | 'prominent' | 'warning';
  className?: string;
  disabled?: boolean;
}

const EnhancedCheckbox: React.FC<EnhancedCheckboxProps> = ({
  id,
  checked,
  onCheckedChange,
  label,
  required = false,
  error,
  description,
  variant = 'default',
  className = '',
  disabled = false
}) => {
  const hasError = !!error;

  const getVariantStyles = () => {
    switch (variant) {
      case 'prominent':
        return {
          container: 'border-2 border-blue-200 bg-blue-50 dark:bg-blue-950 dark:border-blue-800 p-4 rounded-lg',
          checkbox: 'h-6 w-6 border-2',
          label: 'text-lg font-semibold text-blue-900 dark:text-blue-100',
          description: 'text-blue-700 dark:text-blue-300'
        };
      case 'warning':
        return {
          container: 'border-2 border-amber-200 bg-amber-50 dark:bg-amber-950 dark:border-amber-800 p-4 rounded-lg',
          checkbox: 'h-6 w-6 border-2',
          label: 'text-lg font-semibold text-amber-900 dark:text-amber-100',
          description: 'text-amber-700 dark:text-amber-300'
        };
      default:
        return {
          container: 'p-2',
          checkbox: 'h-5 w-5',
          label: 'text-base font-medium',
          description: 'text-gray-600 dark:text-gray-400'
        };
    }
  };

  const styles = getVariantStyles();

  const renderIcon = () => {
    if (variant === 'warning') {
      return <AlertTriangle className="h-5 w-5 text-amber-500 mr-2 flex-shrink-0 mt-0.5" />;
    }
    if (variant === 'prominent' && checked) {
      return <CheckCircle2 className="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />;
    }
    return null;
  };

  return (
    <div className={cn(
      'space-y-2',
      styles.container,
      hasError && 'border-red-300 bg-red-50 dark:bg-red-950 dark:border-red-800',
      className
    )}>
      <div className="flex items-start space-x-3">
        {renderIcon()}
        
        <Checkbox
          id={id}
          checked={checked}
          onCheckedChange={onCheckedChange}
          disabled={disabled}
          className={cn(
            styles.checkbox,
            hasError && 'border-red-500',
            variant === 'prominent' && 'border-blue-500',
            variant === 'warning' && 'border-amber-500'
          )}
        />
        
        <div className="flex-1">
          <Label
            htmlFor={id}
            className={cn(
              'cursor-pointer leading-relaxed',
              styles.label,
              hasError && 'text-red-700 dark:text-red-300',
              disabled && 'text-gray-400 cursor-not-allowed'
            )}
          >
            {label}
            {required && (
              <span className="text-red-500 ml-1 font-bold">*</span>
            )}
          </Label>
          
          {description && (
            <p className={cn(
              'text-sm mt-1',
              styles.description,
              hasError && 'text-red-600 dark:text-red-400'
            )}>
              {description}
            </p>
          )}
        </div>
      </div>

      {hasError && (
        <div className="flex items-center mt-2 text-red-600 dark:text-red-400">
          <AlertTriangle className="h-4 w-4 mr-1" />
          <span className="text-sm font-medium">{error}</span>
        </div>
      )}

      {variant === 'prominent' && !hasError && (
        <div className="mt-3 p-3 bg-white dark:bg-gray-800 rounded border border-blue-200 dark:border-blue-700">
          <p className="text-sm text-gray-600 dark:text-gray-400">
            <strong>Important:</strong> By checking this box, you confirm that you have read, 
            understood, and agree to all terms and conditions stated above. This constitutes 
            a legal declaration and your digital signature.
          </p>
        </div>
      )}

      {variant === 'warning' && (
        <div className="mt-3 p-3 bg-amber-100 dark:bg-amber-900 rounded border border-amber-300 dark:border-amber-700">
          <p className="text-sm text-amber-800 dark:text-amber-200 font-medium">
            ⚠️ This is a legally binding declaration. Please ensure you understand all 
            implications before proceeding.
          </p>
        </div>
      )}
    </div>
  );
};

export default EnhancedCheckbox;