export const formatZimbabweId = (input: string): string => {
  if (!input) return '';

  // Remove non-alphanumeric characters and uppercase letters
  const cleaned = input.replace(/[^0-9a-zA-Z]/g, '').toUpperCase();
  if (!cleaned) return '';

  let prefix = '';
  let midDigits = '';
  let checkLetter = '';
  let suffix = '';

  for (const char of cleaned) {
    if (prefix.length < 2) {
      if (/\d/.test(char)) {
        prefix += char;
      }
      continue;
    }

    if (!checkLetter) {
      if (/\d/.test(char)) {
        if (midDigits.length < 7) {
          midDigits += char;
        }
      } else if (/[A-Z]/.test(char)) {
        checkLetter = char;
      }
      continue;
    }

    if (/\d/.test(char) && suffix.length < 2) {
      suffix += char;
    }
  }

  let formatted = prefix;

  if (prefix.length === 2 && midDigits.length > 0) {
    formatted += `-${midDigits}`;
  } else if (prefix.length === 2 && !midDigits && (checkLetter || suffix)) {
    formatted += '-';
  } else {
    formatted += midDigits;
  }

  if (checkLetter) {
    formatted += ` ${checkLetter}`;
  }

  if (suffix) {
    formatted += ` ${suffix}`;
  }

  return formatted.trim();
};
