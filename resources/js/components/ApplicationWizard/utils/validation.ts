import { WizardData } from '../ApplicationWizard';

export interface ValidationError {
  field: string;
  message: string;
  type?: 'error' | 'warning'; // Added type for different levels of validation errors
}

export interface ValidationResult {
  isValid: boolean;
  errors: ValidationError[];
  warnings?: ValidationError[]; // Added warnings for non-blocking validation issues
  fieldErrors?: Record<string, string>; // Field-specific error messages for form display
  isBlocking?: boolean; // Whether this validation error should block progression
}

// Helper function to format field names for error messages
export const formatFieldName = (field: string): string => {
  // Handle nested fields like 'spouseDetails[0].fullName'
  const parts = field.split('.');
  const lastPart = parts[parts.length - 1];

  // Handle array notation like 'spouseDetails[0]'
  const cleanField = lastPart.replace(/\[\d+\]/g, '');

  // Convert camelCase to Title Case with spaces
  return cleanField
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, (str) => str.toUpperCase())
    .trim();
};

/**
 * Validation rules for different form fields
 * Each rule returns true if validation passes or if the value is empty (unless it's a required field)
 * Use the required rule separately to enforce required fields
 */
export const validationRules = {
  // Basic validation rules
  required: (value: any): boolean => {
    if (value === undefined || value === null) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    if (typeof value === 'number') return true;
    if (Array.isArray(value)) return value.length > 0;
    if (typeof value === 'object') return Object.keys(value).length > 0;
    return !!value;
  },

  // Validate that a field matches another field (useful for password confirmation)
  matches: (value: any, matchValue: any): boolean => {
    if (!value) return true; // Skip if empty
    return value === matchValue;
  },

  email: (value: string): boolean => {
    if (!value) return true; // Skip if empty (use required rule separately)
    // More comprehensive email regex that handles most valid email formats
    const emailRegex = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return emailRegex.test(value.toLowerCase());
  },

  phone: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Zimbabwe phone number format with flexibility - matches backend validation
    const phoneRegex = /^(\+263|0)?[0-9\s\-\(\)]{7,15}$/;
    return phoneRegex.test(value.trim());
  },

  idNumber: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Zimbabwe ID format: Multiple variations accepted
    // Examples: 12-345678 A 12, 12-345678-A-12, 12345678A12, 12-345678 12, etc.
    // The check letter is optional
    const cleaned = value.replace(/\s/g, '').replace(/-/g, '');
    // Pattern: 2 digits, 6-7 digits, optional letter, 2 digits
    const idRegex = /^[0-9]{2}[0-9]{6,7}[A-Z]?[0-9]{2}$/i;
    return idRegex.test(cleaned);
  },

  minLength: (value: string, length: number): boolean => {
    if (!value) return true; // Skip if empty
    return value.length >= length;
  },

  maxLength: (value: string, length: number): boolean => {
    if (!value) return true; // Skip if empty
    return value.length <= length;
  },

  numeric: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    return /^[0-9]+$/.test(value);
  },

  decimal: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Allow up to 2 decimal places
    return /^[0-9]+(\.[0-9]{1,2})?$/.test(value);
  },

  currency: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Currency format with optional $ symbol and thousands separators
    return /^(\$)?([0-9]{1,3},([0-9]{3},)*[0-9]{3}|[0-9]+)(\.[0-9]{1,2})?$/.test(value);
  },

  salaryRange: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Accept salary range dropdown values
    const validRanges = [
      '10-50', '51-100', '101-200', '201-300', '300+', // Government ranges
      '100-200', '201-400', '401-600', '601-800', '801-1000', '1001+' // Other forms ranges
    ];
    return validRanges.includes(value);
  },

  date: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    const dateObj = new Date(value);
    return !isNaN(dateObj.getTime());
  },

  futureDate: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    const dateObj = new Date(value);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to start of day for fair comparison
    return !isNaN(dateObj.getTime()) && dateObj >= today;
  },

  pastDate: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    const dateObj = new Date(value);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to start of day for fair comparison
    return !isNaN(dateObj.getTime()) && dateObj < today;
  },

  minAge: (value: string, age: number): boolean => {
    if (!value) return true; // Skip if empty
    const dateObj = new Date(value);
    if (isNaN(dateObj.getTime())) return false;

    const today = new Date();
    const birthDate = new Date(value);
    let calculatedAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();

    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      calculatedAge--;
    }

    return calculatedAge >= age;
  },

  maxAge: (value: string, age: number): boolean => {
    if (!value) return true; // Skip if empty
    const dateObj = new Date(value);
    if (isNaN(dateObj.getTime())) return false;

    const today = new Date();
    const birthDate = new Date(value);
    let calculatedAge = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();

    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      calculatedAge--;
    }

    return calculatedAge <= age;
  },

  maxValue: (value: number, max: number): boolean => {
    if (value === undefined || value === null) return true;
    return value <= max;
  },

  minValue: (value: number, min: number): boolean => {
    if (value === undefined || value === null) return true;
    return value >= min;
  },

  pattern: (value: string, regex: RegExp): boolean => {
    if (!value) return true; // Skip if empty
    return regex.test(value);
  },

  // Validate business registration number (format varies by country)
  businessRegNumber: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Simple pattern for Zimbabwe business registration numbers
    return /^[A-Z0-9]{1,15}\/[0-9]{4}$|^[0-9]{1,10}\/[0-9]{4}$|^[A-Z0-9\/-]{5,20}$/.test(value);
  },

  // Validate account numbers (general format)
  accountNumber: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Most bank account numbers are 8-17 digits
    return /^[0-9]{8,17}$/.test(value);
  },

  // Validate postal codes (Zimbabwe format)
  postalCode: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    // Zimbabwe postal codes are typically 4-5 digits
    return /^[0-9]{4,5}$/.test(value);
  },

  // Validate that a string contains only letters (and optionally spaces)
  alpha: (value: string, allowSpaces: boolean = true): boolean => {
    if (!value) return true; // Skip if empty
    return allowSpaces
      ? /^[A-Za-z\s]+$/.test(value)
      : /^[A-Za-z]+$/.test(value);
  },

  // Validate that a string contains only letters and numbers (and optionally spaces)
  alphanumeric: (value: string, allowSpaces: boolean = true): boolean => {
    if (!value) return true; // Skip if empty
    return allowSpaces
      ? /^[A-Za-z0-9\s]+$/.test(value)
      : /^[A-Za-z0-9]+$/.test(value);
  },

  // Validate a URL
  url: (value: string): boolean => {
    if (!value) return true; // Skip if empty
    try {
      new URL(value);
      return true;
    } catch {
      return false;
    }
  },

  // Validate a credit card number using Luhn algorithm
  creditCard: (value: string): boolean => {
    if (!value) return true; // Skip if empty

    // Remove spaces and dashes
    const sanitized = value.replace(/[\s-]/g, '');

    // Check if contains only digits
    if (!/^\d+$/.test(sanitized)) return false;

    // Luhn algorithm
    let sum = 0;
    let shouldDouble = false;

    for (let i = sanitized.length - 1; i >= 0; i--) {
      let digit = parseInt(sanitized.charAt(i));

      if (shouldDouble) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }

      sum += digit;
      shouldDouble = !shouldDouble;
    }

    return sum % 10 === 0;
  },
};

// Step-specific validation functions
export const validateLanguageStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  const languageFields = [
    {
      field: 'language',
      rules: [
        { rule: 'required', message: 'Please select a language to continue' }
      ]
    }
  ];

  languageFields.forEach(({ field, rules }) => {
    const value = data[field as keyof WizardData];
    const error = validateField(field, value, rules);
    if (error) {
      errors.push(error);
      fieldErrors[field] = error.message;
    }
  });

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateIntentStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  const intentFields = [
    {
      field: 'intent',
      rules: [
        { rule: 'required', message: 'Please select your intent to continue' }
      ]
    }
  ];

  intentFields.forEach(({ field, rules }) => {
    const value = data[field as keyof WizardData];
    const error = validateField(field, value, rules);
    if (error) {
      errors.push(error);
      fieldErrors[field] = error.message;
    }
  });

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateEmployerStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  // Check if employer is selected
  if (!data.employer) {
    const error = {
      field: 'employer',
      message: 'Please select your employer to continue',
      type: 'error' as const
    };
    errors.push(error);
    fieldErrors['employer'] = error.message;
  }

  // Check if employerName is provided (for display purposes)
  if (!data.employerName) {
    const error = {
      field: 'employerName',
      message: 'Please select your employer category',
      type: 'error' as const
    };
    errors.push(error);
    fieldErrors['employerName'] = error.message;
  }

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateProductStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  // Check if in Cart Mode (Building Materials)
  const isCartMode = data.cart && Array.isArray(data.cart) && data.cart.length > 0;

  // Define fields to validate based on mode
  let productFields: Array<{ field: string; rules: Array<{ rule: string; params?: any; message: string }> }>;

  if (isCartMode) {
    // In Cart Mode, only validate category, amount, and creditTerm
    productFields = [
      {
        field: 'category',
        rules: [
          { rule: 'required', message: 'Please select a product category' }
        ]
      },
      {
        field: 'amount',
        rules: [
          { rule: 'required', message: 'Please enter an amount' },
          { rule: 'minValue', params: 1, message: 'Amount must be greater than 0' }
        ]
      }
    ];
  } else {
    // Check if this is License Courses - amount is calculated in LicenseCoursesStep
    const isLicenseCourses = data.subcategory === 'License Courses' ||
      data.subcategory === 'Driving School' ||
      data.business === 'License Courses';

    // Check if this is Company Registration - amount is calculated later
    const isCompanyReg = data.subcategory === 'Fees and Licensing' ||
      data.business === 'Company Registration';

    // Standard Mode: validate all single-product fields
    productFields = [
      {
        field: 'category',
        rules: [
          { rule: 'required', message: 'Please select a product category' }
        ]
      },
      {
        field: 'subcategory',
        rules: [
          { rule: 'required', message: 'Please select a product subcategory' }
        ]
      },
      {
        field: 'business',
        rules: [
          { rule: 'required', message: 'Please select a business' }
        ]
      }
    ];

    // Only require scale and amount for standard products (not License Courses or Company Reg)
    if (!isLicenseCourses && !isCompanyReg) {
      productFields.push({
        field: 'scale',
        rules: [
          { rule: 'required', message: 'Please select a scale' }
        ]
      });
      productFields.push({
        field: 'amount',
        rules: [
          { rule: 'required', message: 'Please enter an amount' },
          { rule: 'minValue', params: 1, message: 'Amount must be greater than 0' }
        ]
      });
    }
  }

  productFields.forEach(({ field, rules }) => {
    const value = data[field as keyof WizardData];
    const error = validateField(field, value, rules);
    if (error) {
      errors.push(error);
      fieldErrors[field] = error.message;
    }
  });

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateAccountStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  // Check if user has made a choice about having an account
  // hasAccount can be true (has account) or false (doesn't have account)
  // wantsAccount can be true (wants to open account)
  if (data.hasAccount === undefined && data.wantsAccount === undefined) {
    const error = {
      field: 'hasAccount',
      message: 'Please indicate whether you have an account',
      type: 'error' as const
    };
    errors.push(error);
    fieldErrors['hasAccount'] = error.message;
  }

  // Check if accountType is provided when user has or wants an account
  // Skip validation if user explicitly chose to continue without account
  if ((data.hasAccount === true || data.wantsAccount === true) && !data.accountType) {
    const error = {
      field: 'accountType',
      message: 'Please select your account type',
      type: 'error' as const
    };
    errors.push(error);
    fieldErrors['accountType'] = error.message;
  }

  // Note: Account number validation is handled in the form step, not here
  // This step only determines if user has/wants an account and the type

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateFormStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};
  const formResponses = data.formResponses || {};

  // Common validations for all form types
  const commonFields = [
    { field: 'firstName', rules: [{ rule: 'required' }, { rule: 'alpha', params: true }] },
    { field: 'surname', rules: [{ rule: 'required' }, { rule: 'alpha', params: true }] },
    {
      field: 'dateOfBirth',
      rules: [
        { rule: 'required', message: 'Date of birth is required' },
        { rule: 'pastDate', message: 'Date of birth must be in the past' },
        { rule: 'minAge', params: 18, message: 'You must be at least 18 years old' }
      ]
    },
    { field: 'gender', rules: [{ rule: 'required' }] },
    {
      field: 'nationalIdNumber',
      rules: [
        { rule: 'required', message: 'National ID number is required' },
        { rule: 'idNumber', message: 'Please enter a valid ID number format (e.g., 12-345678-A-90)' }
      ]
    },
    {
      field: 'mobile',
      rules: [
        { rule: 'required', message: 'Mobile number is required' },
        { rule: 'phone', message: 'Please enter a valid mobile number (e.g., 0771234567 or +263771234567)' }
      ]
    },
    {
      field: 'emailAddress',
      rules: [
        { rule: 'email', message: 'Please enter a valid email address' }
      ],
      optional: true
    }
  ];

  // Validate common fields
  commonFields.forEach(({ field, rules, optional }) => {
    // Skip validation for optional fields if they're not provided
    if (optional && !formResponses[field]) return;

    const error = validateField(field, formResponses[field], rules);
    if (error) {
      errors.push(error);
      fieldErrors[field] = error.message;
    }
  });

  // Form-specific validations based on formId
  if (data.formId) {
    switch (data.formId) {
      case 'account_holder_loan_application.json':
        // Account holders loan form validations
        const accountHolderFields = [
          {
            field: 'employerName',
            rules: [
              { rule: 'required', message: 'Employer name is required' }
            ]
          },
          {
            field: 'currentNetSalary',
            rules: [
              { rule: 'required', message: 'Current net salary is required' },
              { rule: 'salaryRange', message: 'Please select a valid salary range' }
            ]
          },
          {
            field: 'jobTitle',
            rules: [
              { rule: 'required', message: 'Job title is required' }
            ]
          },
          {
            field: 'employerAddress',
            rules: [
              { rule: 'required', message: 'Employer address is required' }
            ]
          },
          {
            field: 'dateOfEmployment',
            rules: [
              { rule: 'required', message: 'Date of employment is required' }
            ]
          },
          {
            field: 'loanAmount',
            rules: [
              { rule: 'required', message: 'Loan amount is required' },
              { rule: 'decimal', message: 'Please enter a valid loan amount' }
            ]
          },
          {
            field: 'loanTenure',
            rules: [
              { rule: 'required', message: 'Loan tenure is required' },
              { rule: 'numeric', message: 'Please enter a valid loan tenure' }
            ]
          },
          {
            field: 'purposeOfLoan',
            rules: [
              { rule: 'required', message: 'Purpose of loan is required' }
            ]
          }
        ];

        // Validate account holder fields
        accountHolderFields.forEach(({ field, rules }) => {
          const error = validateField(field, formResponses[field], rules);
          if (error) {
            errors.push(error);
            fieldErrors[field] = error.message;
          }
        });

        // Validate next of kin (spouse details)
        if (!formResponses.spouseDetails ||
          !formResponses.spouseDetails[0] ||
          !validationRules.required(formResponses.spouseDetails[0].fullName)) {
          const error = {
            field: 'spouseDetails[0].fullName',
            message: 'At least one next of kin is required'
          };
          errors.push(error);
          fieldErrors['spouseDetails[0].fullName'] = error.message;
        }

        if (formResponses.spouseDetails &&
          formResponses.spouseDetails[0] &&
          validationRules.required(formResponses.spouseDetails[0].fullName)) {

          // Validate relationship
          if (!validationRules.required(formResponses.spouseDetails[0].relationship)) {
            const error = {
              field: 'spouseDetails[0].relationship',
              message: 'Relationship is required for next of kin'
            };
            errors.push(error);
            fieldErrors['spouseDetails[0].relationship'] = error.message;
          }

          // Validate phone number
          if (!validationRules.required(formResponses.spouseDetails[0].phoneNumber)) {
            const error = {
              field: 'spouseDetails[0].phoneNumber',
              message: 'Phone number is required for next of kin'
            };
            errors.push(error);
            fieldErrors['spouseDetails[0].phoneNumber'] = error.message;
          } else if (!validationRules.phone(formResponses.spouseDetails[0].phoneNumber as string)) {
            const error = {
              field: 'spouseDetails[0].phoneNumber',
              message: 'Please enter a valid phone number for next of kin'
            };
            errors.push(error);
            fieldErrors['spouseDetails[0].phoneNumber'] = error.message;
          }
        }
        break;

      case 'ssb_account_opening_form.json':
        // SSB loan form validations
        const ssbFields = [
          {
            field: 'employeeNumber',
            rules: [
              { rule: 'required', message: 'Employee number is required' },
              { rule: 'alphanumeric', params: false, message: 'Employee number must contain only letters and numbers' }
            ]
          },
          {
            field: 'ministry',
            rules: [
              { rule: 'required', message: 'Ministry is required' }
            ]
          },
          {
            field: 'netSalary',
            rules: [
              { rule: 'required', message: 'Net salary is required' },
              { rule: 'salaryRange', message: 'Please select a valid salary range' }
            ]
          },
          {
            field: 'responsiblePaymaster',
            rules: [
              { rule: 'required', message: 'Responsible paymaster is required' }
            ]
          },
          {
            field: 'responsibleMinistry',
            rules: [
              { rule: 'required', message: 'Responsible ministry is required' }
            ]
          },
          {
            field: 'loanAmount',
            rules: [
              { rule: 'required', message: 'Loan amount is required' },
              { rule: 'decimal', message: 'Please enter a valid loan amount' }
            ]
          },
          {
            field: 'loanTenure',
            rules: [
              { rule: 'required', message: 'Loan tenure is required' },
              { rule: 'numeric', message: 'Please enter a valid loan tenure' }
            ]
          }
        ];

        // Validate SSB fields
        ssbFields.forEach(({ field, rules }) => {
          const error = validateField(field, formResponses[field], rules);
          if (error) {
            errors.push(error);
            fieldErrors[field] = error.message;
          }
        });
        break;

      case 'individual_account_opening.json':
        // ZB account opening form validations
        const zbFields = [
          {
            field: 'residentialAddress',
            rules: [
              { rule: 'required', message: 'Residential address is required' }
            ]
          },
          {
            field: 'maritalStatus',
            rules: [
              { rule: 'required', message: 'Marital status is required' }
            ]
          },
          {
            field: 'nationality',
            rules: [
              { rule: 'required', message: 'Nationality is required' }
            ]
          },
          {
            field: 'countryOfResidence',
            rules: [
              { rule: 'required', message: 'Country of residence is required' }
            ]
          },
          {
            field: 'accountCurrency',
            rules: [
              { rule: 'required', message: 'Account currency is required' }
            ]
          },
          {
            field: 'serviceCenter',
            rules: [
              { rule: 'required', message: 'Service center is required' }
            ]
          },
          {
            field: 'grossMonthlySalary',
            rules: [
              { rule: 'required', message: 'Monthly salary is required' },
              { rule: 'salaryRange', message: 'Please select a valid salary range' }
            ]
          }
        ];

        // Validate ZB fields
        zbFields.forEach(({ field, rules }) => {
          const error = validateField(field, formResponses[field], rules);
          if (error) {
            errors.push(error);
            fieldErrors[field] = error.message;
          }
        });

        // Validate next of kin
        if (!formResponses.spouseDetails ||
          !formResponses.spouseDetails[0] ||
          !validationRules.required(formResponses.spouseDetails[0].fullName)) {
          const error = {
            field: 'spouseDetails[0].fullName',
            message: 'At least one next of kin is required'
          };
          errors.push(error);
          fieldErrors['spouseDetails[0].fullName'] = error.message;
        }

        // Validate declaration
        if (!formResponses.declaration || !formResponses.declaration.acknowledged) {
          const error = {
            field: 'declaration.acknowledged',
            message: 'You must acknowledge the declaration'
          };
          errors.push(error);
          fieldErrors['declaration.acknowledged'] = error.message;
        }
        break;

      case 'smes_business_account_opening.json':
        // SME business form validations
        const smeFields = [
          {
            field: 'businessName',
            rules: [
              { rule: 'required', message: 'Business name is required' }
            ]
          },
          {
            field: 'businessRegistrationNumber',
            rules: [
              { rule: 'required', message: 'Business registration number is required' },
              { rule: 'businessRegNumber', message: 'Please enter a valid business registration number format (e.g., ABC123/2023)' }
            ]
          },
          {
            field: 'businessType',
            rules: [
              { rule: 'required', message: 'Business type is required' }
            ]
          },
          {
            field: 'businessAddress',
            rules: [
              { rule: 'required', message: 'Business address is required' }
            ]
          },
          {
            field: 'businessPhone',
            rules: [
              { rule: 'required', message: 'Business phone is required' },
              { rule: 'phone', message: 'Please enter a valid phone number (e.g., 0771234567 or +263771234567)' }
            ]
          },
          {
            field: 'businessEmail',
            rules: [
              { rule: 'email', message: 'Please enter a valid email address' }
            ],
            optional: true
          },
          {
            field: 'businessIndustry',
            rules: [
              { rule: 'required', message: 'Business industry is required' }
            ]
          },
          {
            field: 'businessYearsOperating',
            rules: [
              { rule: 'required', message: 'Years operating is required' },
              { rule: 'numeric', message: 'Please enter a valid number of years' }
            ]
          },
          {
            field: 'businessAnnualRevenue',
            rules: [
              { rule: 'required', message: 'Annual revenue is required' },
              { rule: 'salaryRange', message: 'Please select a valid revenue range' }
            ]
          },
          {
            field: 'netProfit',
            rules: [
              { rule: 'required', message: 'Net profit is required' },
              { rule: 'salaryRange', message: 'Please select a valid profit range' }
            ]
          }
        ];

        // Validate SME fields
        smeFields.forEach(({ field, rules, optional }) => {
          // Skip validation for optional fields if they're not provided
          if (optional && !formResponses[field]) return;

          const error = validateField(field, formResponses[field], rules);
          if (error) {
            errors.push(error);
            fieldErrors[field] = error.message;
          }
        });

        // Validate directors' personal details
        const directorFields = [
          {
            field: 'directorsPersonalDetails.firstName',
            rules: [
              { rule: 'required', message: 'Director\'s first name is required' },
              { rule: 'alpha', params: true, message: 'Director\'s first name must contain only letters' }
            ]
          },
          {
            field: 'directorsPersonalDetails.surname',
            rules: [
              { rule: 'required', message: 'Director\'s surname is required' },
              { rule: 'alpha', params: true, message: 'Director\'s surname must contain only letters' }
            ]
          },
          {
            field: 'directorsPersonalDetails.idNumber',
            rules: [
              { rule: 'required', message: 'Director\'s ID number is required' },
              { rule: 'idNumber', message: 'Please enter a valid ID number format (e.g., 12-345678-A-90)' }
            ]
          }
        ];

        // Validate director fields
        directorFields.forEach(({ field, rules }) => {
          const fieldParts = field.split('.');
          const directorDetails = formResponses.directorsPersonalDetails as Record<string, unknown> | undefined;
          const value = directorDetails ? directorDetails[fieldParts[1]] : undefined;

          const error = validateField(field, value, rules);
          if (error) {
            errors.push(error);
            fieldErrors[field] = error.message;
          }
        });

        // Validate at least one reference
        if (!formResponses.references ||
          !formResponses.references[0] ||
          !validationRules.required(formResponses.references[0].name)) {
          const error = {
            field: 'references[0].name',
            message: 'At least one reference is required'
          };
          errors.push(error);
          fieldErrors['references[0].name'] = error.message;
        }

        if (formResponses.references &&
          formResponses.references[0] &&
          validationRules.required(formResponses.references[0].name) &&
          !validationRules.required(formResponses.references[0].phoneNumber)) {
          const error = {
            field: 'references[0].phoneNumber',
            message: 'Phone number is required for reference'
          };
          errors.push(error);
          fieldErrors['references[0].phoneNumber'] = error.message;
        } else if (formResponses.references &&
          formResponses.references[0] &&
          formResponses.references[0].phoneNumber &&
          !validationRules.phone(formResponses.references[0].phoneNumber as string)) {
          const error = {
            field: 'references[0].phoneNumber',
            message: 'Please enter a valid phone number for reference'
          };
          errors.push(error);
          fieldErrors['references[0].phoneNumber'] = error.message;
        }
        break;
    }
  }

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

export const validateDocumentsStep = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  // Check if documents object exists
  if (!data.documents) {
    errors.push({
      field: 'documents',
      message: 'No documents uploaded'
    });
    fieldErrors['documents'] = 'No documents uploaded';
    return {
      isValid: false,
      errors,
      fieldErrors
    };
  }

  // Check for selfie
  if (!data.documents.selfie) {
    errors.push({
      field: 'selfie',
      message: 'Selfie photo is required'
    });
    fieldErrors['selfie'] = 'Selfie photo is required';
  }

  // Check for signature
  if (!data.documents.signature) {
    errors.push({
      field: 'signature',
      message: 'Digital signature is required'
    });
    fieldErrors['signature'] = 'Digital signature is required';
  }

  // Check for required documents based on application type
  const uploadedDocs = data.documents.uploadedDocuments || {};

  // Always required documents
  const requiredDocTypes = ['national_id'];

  // Add employer-specific required documents
  if (data.employer === 'entrepreneur') {
    requiredDocTypes.push('business_license');
  }

  // Note: Employment letter requirement removed - verification handled by admin/backend

  // Validate each required document type
  requiredDocTypes.forEach(docType => {
    const docs = uploadedDocs[docType] || [];

    if (docs.length === 0) {
      const docName = formatDocumentName(docType);
      errors.push({
        field: docType,
        message: `${docName} is required`
      });
      fieldErrors[docType] = `${docName} is required`;
    } else {
      // Check if any document has validation errors
      const hasErrors = docs.some(doc => doc.validationErrors && doc.validationErrors.length > 0);
      if (hasErrors) {
        const docName = formatDocumentName(docType);
        errors.push({
          field: docType,
          message: `${docName} has validation errors`
        });
        fieldErrors[docType] = `${docName} has validation errors`;
      }
    }
  });

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

// Helper function to format document type names for error messages
const formatDocumentName = (docType: string): string => {
  return docType
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

export const validateDocuments = (data: Partial<WizardData>): ValidationResult => {
  const errors: ValidationError[] = [];
  const fieldErrors: Record<string, string> = {};

  if (!data.documents || !data.documents.uploadedDocuments) {
    const error = {
      field: 'documents',
      message: 'Please upload the required documents'
    };
    errors.push(error);
    fieldErrors['documents'] = error.message;

    return {
      isValid: false,
      errors,
      fieldErrors
    };
  }

  // Determine required document types based on form type
  const requiredDocTypes = ['national_id'];

  // Add form-specific required documents
  if (data.formId) {
    switch (data.formId) {
      case 'account_holder_loan_application.json':
        requiredDocTypes.push('payslip', 'bank_statement');
        break;
      case 'ssb_account_opening_form.json':
        requiredDocTypes.push('payslip');
        break;
      case 'individual_account_opening.json':
        requiredDocTypes.push('passport_photo');
        break;
      case 'smes_business_account_opening.json':
        requiredDocTypes.push('business_registration', 'financial_statements', 'director_id');
        break;
    }
  }

  // Check if required document types are uploaded
  for (const docType of requiredDocTypes) {
    if (!data.documents.uploadedDocuments[docType] || data.documents.uploadedDocuments[docType].length === 0) {
      const formattedDocType = docType
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());

      const error = {
        field: docType,
        message: `Please upload your ${formattedDocType}`
      };
      errors.push(error);
      fieldErrors[docType] = error.message;
    }
  }

  // Check for failed uploads
  for (const docType in data.documents.uploadedDocuments) {
    const docs = data.documents.uploadedDocuments[docType];
    for (const doc of docs) {
      if (doc.status === 'failed') {
        const error = {
          field: `${docType}_${doc.id}`,
          message: `Upload failed for ${doc.name}. Please try again.`
        };
        errors.push(error);
        fieldErrors[`${docType}_${doc.id}`] = error.message;
      }

      // Validate file size (max 5MB)
      if (doc.size > 5 * 1024 * 1024) {
        const error = {
          field: `${docType}_${doc.id}`,
          message: `${doc.name} exceeds the maximum file size of 5MB.`
        };
        errors.push(error);
        fieldErrors[`${docType}_${doc.id}`] = error.message;
      }

      // Validate file type based on document type
      const allowedImageTypes = ['image/jpeg', 'image/png', 'image/jpg'];
      const allowedDocTypes = ['application/pdf', ...allowedImageTypes];

      if (!allowedDocTypes.includes(doc.type)) {
        const error = {
          field: `${docType}_${doc.id}`,
          message: `${doc.name} has an invalid file type. Please upload PDF or image files.`
        };
        errors.push(error);
        fieldErrors[`${docType}_${doc.id}`] = error.message;
      }
    }
  }

  // Check for selfie and signature
  if (!data.documents?.selfie) {
    const error = {
      field: 'selfie',
      message: 'Please upload your selfie photo'
    };
    errors.push(error);
    fieldErrors['selfie'] = error.message;
  }

  if (!data.documents?.signature) {
    const error = {
      field: 'signature',
      message: 'Please provide your signature'
    };
    errors.push(error);
    fieldErrors['signature'] = error.message;
  }

  return {
    isValid: errors.length === 0,
    errors,
    fieldErrors
  };
};

// This function is replaced by the enhanced version below

// Validate a complete form based on field definitions
export const validateForm = (
  formData: Record<string, any>,
  fieldDefinitions: Record<string, Array<{ rule: string, params?: any }>>
): ValidationResult => {
  const errors: ValidationError[] = [];
  const warnings: ValidationError[] = [];

  for (const [field, rules] of Object.entries(fieldDefinitions)) {
    const error = validateField(field, formData[field], rules);
    if (error) {
      if (error.type === 'warning') {
        warnings.push(error);
      } else {
        errors.push(error);
      }
    }
  }

  return {
    isValid: errors.length === 0,
    errors,
    warnings: warnings.length > 0 ? warnings : undefined
  };
};

// Main validation function that selects the appropriate validator based on step
export const validateStep = (step: string, data: Partial<WizardData>): ValidationResult => {
  let validationResult: ValidationResult;

  switch (step) {
    case 'language':
      validationResult = validateLanguageStep(data);
      break;
    case 'intent':
      validationResult = validateIntentStep(data);
      break;
    case 'employer':
      validationResult = validateEmployerStep(data);
      break;
    case 'product':
      validationResult = validateProductStep(data);
      break;
    case 'account':
      validationResult = validateAccountStep(data);
      break;
    case 'creditType':
      validationResult = { isValid: true, errors: [] };
      break;
    case 'form':
      validationResult = validateFormStep(data);
      break;
    case 'documents':
      validationResult = validateDocumentsStep(data);
      break;
    case 'summary':
      // Summary step doesn't need validation as it's just displaying information
      validationResult = { isValid: true, errors: [] };
      break;
    default:
      validationResult = { isValid: true, errors: [] };
  }

  // Convert errors to fieldErrors format for easier consumption by form components
  if (validationResult.errors && validationResult.errors.length > 0) {
    const fieldErrors: Record<string, string> = {};
    validationResult.errors.forEach(error => {
      fieldErrors[error.field] = error.message;
    });
    validationResult.fieldErrors = fieldErrors;
  }

  return validationResult;
};

// Enhanced field validation function with better error messages
export const validateField = (field: string, value: any, rules: Array<{ rule: string, params?: any, message?: string }>): ValidationError | null => {
  for (const { rule, params, message } of rules) {
    // Skip empty validation for required rule as it's handled differently
    if (rule === 'required') {
      if (!validationRules.required(value)) {
        return {
          field,
          message: message || `${formatFieldName(field)} is required`,
          type: 'error'
        };
      }
    } else if (rule in validationRules) {
      // For other rules, check if value exists first
      if (value !== undefined && value !== null && value !== '') {
        // @ts-ignore - Dynamic access to validation rules
        const isValid = params !== undefined ? validationRules[rule](value, params) : validationRules[rule](value);

        if (!isValid) {
          return {
            field,
            message: message || getDefaultErrorMessage(field, rule, params),
            type: 'error'
          };
        }
      }
    }
  }

  return null;
};

// Helper function to generate default error messages based on validation rule
export const getDefaultErrorMessage = (field: string, rule: string, params?: any): string => {
  const fieldName = formatFieldName(field);

  switch (rule) {
    case 'required':
      return `${fieldName} is required`;
    case 'email':
      return `Please enter a valid email address`;
    case 'phone':
      return `Please enter a valid phone number`;
    case 'idNumber':
      return `Please enter a valid ID number`;
    case 'minLength':
      return `${fieldName} must be at least ${params} characters`;
    case 'maxLength':
      return `${fieldName} cannot exceed ${params} characters`;
    case 'numeric':
      return `${fieldName} must contain only numbers`;
    case 'decimal':
      return `${fieldName} must be a valid decimal number`;
    case 'currency':
      return `${fieldName} must be a valid currency amount`;
    case 'salaryRange':
      return `Please select a valid salary range`;
    case 'date':
      return `Please enter a valid date`;
    case 'futureDate':
      return `${fieldName} must be a future date`;
    case 'pastDate':
      return `${fieldName} must be a past date`;
    case 'minAge':
      return `You must be at least ${params} years old`;
    case 'maxAge':
      return `You cannot be older than ${params} years`;
    case 'minValue':
      return `${fieldName} must be at least ${params}`;
    case 'maxValue':
      return `${fieldName} cannot exceed ${params}`;
    case 'pattern':
      return `${fieldName} format is invalid`;
    case 'businessRegNumber':
      return `Please enter a valid business registration number`;
    case 'accountNumber':
      return `Please enter a valid account number`;
    case 'postalCode':
      return `Please enter a valid postal code`;
    case 'alpha':
      return `${fieldName} must contain only letters`;
    case 'alphanumeric':
      return `${fieldName} must contain only letters and numbers`;
    case 'url':
      return `Please enter a valid URL`;
    case 'creditCard':
      return `Please enter a valid credit card number`;
    case 'matches':
      return `${fieldName} does not match`;
    default:
      return `${fieldName} is invalid`;
  }
};

// This function is replaced by the enhanced version above

// Function to convert validation errors to field errors for form display
export const errorsToFieldErrors = (errors: ValidationError[]): Record<string, string> => {
  const fieldErrors: Record<string, string> = {};

  errors.forEach(error => {
    fieldErrors[error.field] = error.message;
  });

  return fieldErrors;
};
