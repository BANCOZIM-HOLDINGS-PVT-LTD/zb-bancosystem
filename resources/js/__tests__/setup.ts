import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock IntersectionObserver
global.IntersectionObserver = vi
  .fn()
  .mockImplementation((_callback: IntersectionObserverCallback) => {
    return {
      observe: vi.fn(),
      unobserve: vi.fn(),
      disconnect: vi.fn(),
      takeRecords: vi.fn().mockReturnValue([]),
      root: null,
      rootMargin: '',
      thresholds: [],
    } as unknown as IntersectionObserver;
  }) as unknown as typeof IntersectionObserver;

// Mock ResizeObserver
global.ResizeObserver = vi
  .fn()
  .mockImplementation((_callback: ResizeObserverCallback) => {
    return {
      observe: vi.fn(),
      unobserve: vi.fn(),
      disconnect: vi.fn(),
    } as unknown as ResizeObserver;
  }) as unknown as typeof ResizeObserver;

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock scrollTo
Object.defineProperty(window, 'scrollTo', {
  writable: true,
  value: vi.fn(),
});

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn(),
};

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});

// Mock sessionStorage
const sessionStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn(),
};

Object.defineProperty(window, 'sessionStorage', {
  value: sessionStorageMock,
});

// Mock URL.createObjectURL
Object.defineProperty(URL, 'createObjectURL', {
  writable: true,
  value: vi.fn(() => 'mocked-url'),
});

Object.defineProperty(URL, 'revokeObjectURL', {
  writable: true,
  value: vi.fn(),
});

// Mock File and FileReader
global.File = class MockFile extends Blob implements File {
  lastModified: number;
  name: string;
  webkitRelativePath = '';

  constructor(chunks: BlobPart[], name: string, options: FilePropertyBag = {}) {
    super(chunks, options);
    this.name = name;
    this.lastModified = options.lastModified ?? Date.now();
  }
} as unknown as typeof File;

global.FileReader = class MockFileReader {
  result: string | ArrayBuffer | null = null;
  error: DOMException | null = null;
  readyState = 0;
  onload: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;
  onerror: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;
  onloadend: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;
  onabort: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;
  onloadstart: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;
  onprogress: ((this: FileReader, ev: ProgressEvent<FileReader>) => unknown) | null = null;

  private emit(eventName: 'load' | 'loadend' | 'error' | 'abort') {
    const handler = this[`on${eventName}` as keyof MockFileReader];
    if (typeof handler === 'function') {
      (handler as (this: FileReader, ev: ProgressEvent<FileReader>) => unknown).call(
        this as unknown as FileReader,
        new ProgressEvent(eventName) as ProgressEvent<FileReader>
      );
    }
  }

  readAsDataURL(_file: Blob) {
    this.readyState = 2;
    this.result = 'data:text/plain;base64,dGVzdA==';
    this.emit('load');
    this.emit('loadend');
  }

  readAsText(_file: Blob) {
    this.readyState = 2;
    this.result = 'test content';
    this.emit('load');
    this.emit('loadend');
  }

  abort() {
    this.readyState = 2;
    this.result = null;
    this.emit('abort');
    this.emit('loadend');
  }

  addEventListener(): void {}
  removeEventListener(): void {}
  dispatchEvent(): boolean {
    return true;
  }
} as unknown as typeof FileReader;

// Mock crypto.randomUUID
Object.defineProperty(global, 'crypto', {
  value: {
    randomUUID: vi.fn(() => 'mocked-uuid-1234-5678-9012'),
    getRandomValues: vi.fn((arr: any) => {
      for (let i = 0; i < arr.length; i++) {
        arr[i] = Math.floor(Math.random() * 256);
      }
      return arr;
    }),
  },
});

// Mock performance.now
Object.defineProperty(global, 'performance', {
  value: {
    now: vi.fn(() => Date.now()),
    mark: vi.fn(),
    measure: vi.fn(),
    getEntriesByName: vi.fn(() => []),
    getEntriesByType: vi.fn(() => []),
  },
});

// Mock fetch globally
global.fetch = vi.fn();

// Mock CSRF token
Object.defineProperty(document, 'querySelector', {
  writable: true,
  value: vi.fn((selector: string) => {
    if (selector === 'meta[name="csrf-token"]') {
      return {
        getAttribute: vi.fn(() => 'mock-csrf-token'),
      };
    }
    return null;
  }),
});

// Mock console methods for cleaner test output
const originalConsoleError = console.error;
const originalConsoleWarn = console.warn;

beforeEach(() => {
  // Reset all mocks before each test
  vi.clearAllMocks();
  
  // Reset localStorage and sessionStorage
  localStorageMock.getItem.mockClear();
  localStorageMock.setItem.mockClear();
  localStorageMock.removeItem.mockClear();
  localStorageMock.clear.mockClear();
  
  sessionStorageMock.getItem.mockClear();
  sessionStorageMock.setItem.mockClear();
  sessionStorageMock.removeItem.mockClear();
  sessionStorageMock.clear.mockClear();
  
  // Mock fetch to return successful response by default
  (global.fetch as any).mockResolvedValue({
    ok: true,
    status: 200,
    json: async () => ({ success: true }),
    text: async () => 'success',
    headers: new Headers(),
  });
});

afterEach(() => {
  // Clean up after each test
  vi.restoreAllMocks();
});

// Suppress console errors and warnings in tests unless explicitly needed
beforeAll(() => {
  console.error = vi.fn();
  console.warn = vi.fn();
});

afterAll(() => {
  console.error = originalConsoleError;
  console.warn = originalConsoleWarn;
});

// Global test utilities
export const createMockFile = (name: string, content: string, type: string = 'text/plain') => {
  return new File([content], name, { type });
};

export const createMockEvent = (type: string, properties: any = {}) => {
  return {
    type,
    preventDefault: vi.fn(),
    stopPropagation: vi.fn(),
    target: { value: '' },
    currentTarget: { value: '' },
    ...properties,
  };
};

export const waitForNextTick = () => new Promise(resolve => setTimeout(resolve, 0));

export const mockFetchResponse = (data: any, options: { status?: number; ok?: boolean } = {}) => {
  const { status = 200, ok = true } = options;
  
  (global.fetch as any).mockResolvedValueOnce({
    ok,
    status,
    json: async () => data,
    text: async () => JSON.stringify(data),
    headers: new Headers(),
  });
};

export const mockFetchError = (error: Error) => {
  (global.fetch as any).mockRejectedValueOnce(error);
};
