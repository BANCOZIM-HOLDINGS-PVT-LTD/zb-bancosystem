import React, { Suspense, lazy, ComponentType } from 'react';
import { Loader2 } from 'lucide-react';

interface LazyComponentProps {
    fallback?: React.ReactNode;
    error?: React.ReactNode;
}

/**
 * Higher-order component for lazy loading with error boundaries
 */
export function withLazyLoading<P extends object>(
    importFunc: () => Promise<{ default: ComponentType<P> }>,
    options: LazyComponentProps = {}
) {
    const LazyComponent = lazy(importFunc);

    return function LazyWrapper(props: P) {
        const defaultFallback = (
            <div className="flex items-center justify-center p-8">
                <div className="flex items-center space-x-2 text-gray-600">
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Loading...</span>
                </div>
            </div>
        );

        return (
            <Suspense fallback={options.fallback || defaultFallback}>
                <LazyComponent {...props} />
            </Suspense>
        );
    };
}

/**
 * Loading skeleton for form components
 */
export function FormLoadingSkeleton() {
    return (
        <div className="space-y-6 animate-pulse">
            <div className="space-y-2">
                <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                <div className="h-10 bg-gray-200 rounded"></div>
            </div>
            <div className="space-y-2">
                <div className="h-4 bg-gray-200 rounded w-1/3"></div>
                <div className="h-10 bg-gray-200 rounded"></div>
            </div>
            <div className="space-y-2">
                <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                <div className="h-24 bg-gray-200 rounded"></div>
            </div>
            <div className="flex space-x-4">
                <div className="h-10 bg-gray-200 rounded w-24"></div>
                <div className="h-10 bg-gray-200 rounded w-24"></div>
            </div>
        </div>
    );
}

/**
 * Loading skeleton for wizard steps
 */
export function WizardStepSkeleton() {
    return (
        <div className="space-y-8 animate-pulse">
            {/* Header */}
            <div className="space-y-2">
                <div className="h-6 bg-gray-200 rounded w-1/2"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>

            {/* Progress bar */}
            <div className="space-y-2">
                <div className="flex justify-between">
                    {[...Array(5)].map((_, i) => (
                        <div key={i} className="h-2 bg-gray-200 rounded w-16"></div>
                    ))}
                </div>
            </div>

            {/* Content */}
            <FormLoadingSkeleton />
        </div>
    );
}

/**
 * Loading skeleton for document upload
 */
export function DocumentUploadSkeleton() {
    return (
        <div className="space-y-6 animate-pulse">
            <div className="border-2 border-dashed border-gray-200 rounded-lg p-8">
                <div className="text-center space-y-4">
                    <div className="h-12 w-12 bg-gray-200 rounded-full mx-auto"></div>
                    <div className="space-y-2">
                        <div className="h-4 bg-gray-200 rounded w-1/2 mx-auto"></div>
                        <div className="h-3 bg-gray-200 rounded w-1/3 mx-auto"></div>
                    </div>
                </div>
            </div>
            
            <div className="space-y-4">
                {[...Array(3)].map((_, i) => (
                    <div key={i} className="flex items-center space-x-4 p-4 border border-gray-200 rounded">
                        <div className="h-10 w-10 bg-gray-200 rounded"></div>
                        <div className="flex-1 space-y-2">
                            <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                            <div className="h-3 bg-gray-200 rounded w-1/4"></div>
                        </div>
                        <div className="h-8 w-8 bg-gray-200 rounded"></div>
                    </div>
                ))}
            </div>
        </div>
    );
}

/**
 * Performance monitoring hook for components
 */
export function usePerformanceMonitoring(componentName: string) {
    React.useEffect(() => {
        const startTime = performance.now();
        
        return () => {
            const endTime = performance.now();
            const renderTime = endTime - startTime;
            
            // Log slow renders in development
            if (process.env.NODE_ENV === 'development' && renderTime > 100) {
                console.warn(`Slow render detected in ${componentName}: ${renderTime.toFixed(2)}ms`);
            }
            
            // Send to analytics in production
            if (process.env.NODE_ENV === 'production' && renderTime > 500) {
                // Send to your analytics service
                fetch('/api/client-performance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        component: componentName,
                        render_time: renderTime,
                        timestamp: new Date().toISOString(),
                        url: window.location.href,
                    }),
                }).catch(error => {
                    console.error('Failed to send performance data:', error);
                });
            }
        };
    }, [componentName]);
}

/**
 * Memoized component wrapper for expensive components
 */
export function withMemoization<P extends object>(
    Component: ComponentType<P>,
    areEqual?: (prevProps: P, nextProps: P) => boolean
) {
    return React.memo(Component, areEqual);
}

/**
 * Virtual scrolling hook for large lists
 */
export function useVirtualScrolling(
    items: any[],
    itemHeight: number,
    containerHeight: number
) {
    const [scrollTop, setScrollTop] = React.useState(0);
    
    const startIndex = Math.floor(scrollTop / itemHeight);
    const endIndex = Math.min(
        startIndex + Math.ceil(containerHeight / itemHeight) + 1,
        items.length
    );
    
    const visibleItems = items.slice(startIndex, endIndex);
    const totalHeight = items.length * itemHeight;
    const offsetY = startIndex * itemHeight;
    
    const handleScroll = React.useCallback((event: React.UIEvent<HTMLDivElement>) => {
        setScrollTop(event.currentTarget.scrollTop);
    }, []);
    
    return {
        visibleItems,
        totalHeight,
        offsetY,
        handleScroll,
        startIndex,
        endIndex,
    };
}

/**
 * Debounced value hook for performance optimization
 */
export function useDebounce<T>(value: T, delay: number): T {
    const [debouncedValue, setDebouncedValue] = React.useState<T>(value);

    React.useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}

/**
 * Intersection observer hook for lazy loading
 */
export function useIntersectionObserver<T extends Element>(
    ref: React.RefObject<T | null>,
    options: IntersectionObserverInit = {}
) {
    const [isIntersecting, setIsIntersecting] = React.useState(false);

    React.useEffect(() => {
        const element = ref.current;
        if (!element) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                setIsIntersecting(entry.isIntersecting);
            },
            options
        );

        observer.observe(element);

        return () => {
            observer.unobserve(element);
        };
    }, [ref, options]);

    return isIntersecting;
}

/**
 * Image lazy loading component
 */
interface LazyImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
    src: string;
    alt: string;
    placeholder?: string;
    className?: string;
}

export function LazyImage({ src, alt, placeholder, className, ...props }: LazyImageProps) {
    const imgRef = React.useRef<HTMLImageElement>(null);
    const isVisible = useIntersectionObserver(imgRef, { threshold: 0.1 });
    const [isLoaded, setIsLoaded] = React.useState(false);
    const [hasError, setHasError] = React.useState(false);

    const handleLoad = () => setIsLoaded(true);
    const handleError = () => setHasError(true);

    return (
        <div ref={imgRef} className={`relative ${className}`}>
            {isVisible && !hasError && (
                <img
                    src={src}
                    alt={alt}
                    onLoad={handleLoad}
                    onError={handleError}
                    className={`transition-opacity duration-300 ${
                        isLoaded ? 'opacity-100' : 'opacity-0'
                    }`}
                    {...props}
                />
            )}
            
            {(!isVisible || !isLoaded || hasError) && (
                <div className="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center">
                    {placeholder ? (
                        <img src={placeholder} alt="" className="opacity-50" />
                    ) : (
                        <div className="text-gray-400 text-sm">Loading...</div>
                    )}
                </div>
            )}
        </div>
    );
}
