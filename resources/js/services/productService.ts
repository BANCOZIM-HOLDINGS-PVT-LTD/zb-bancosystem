import axios from 'axios';

// Types matching the existing frontend structure
export interface BusinessType {
  id?: number;
  name: string;
  basePrice: number;
  image_url?: string;
  colors?: string[];
  scales: {
    id?: number;
    name: string;
    multiplier: number;
    custom_price?: number;
  }[];
  tenure?: number;
}

export interface Series {
  id: number;
  name: string;
  image_url?: string;
  products: BusinessType[];
}

export interface Subcategory {
  name: string;
  series?: Series[];
  businesses: BusinessType[];
}

export interface Category {
  id: string;
  name: string;
  emoji: string;
  subcategories: Subcategory[];
}

export interface CreditTermOption {
  months: number;
  monthlyPayment: number;
}

class ProductService {
  private baseUrl = '/api/products';
  private cache: Map<string, { data: Category[], timestamp: number }> = new Map();
  private cacheExpiry = 5 * 60 * 1000; // 5 minutes

  /**
   * Get all product categories with caching
   * @param intent - Optional intent type ('hirePurchase' or 'microBiz') to filter products
   */
  async getProductCategories(intent?: string): Promise<Category[]> {
    const cacheKey = intent || 'all';
    const cached = this.cache.get(cacheKey);

    // Check cache first
    if (cached && (Date.now() - cached.timestamp) < this.cacheExpiry) {
      return cached.data;
    }

    try {
      const url = intent
        ? `${this.baseUrl}/frontend-catalog?intent=${intent}`
        : `${this.baseUrl}/frontend-catalog`;
      const response = await axios.get<Category[]>(url);
      const categories = response.data ?? [];

      this.cache.set(cacheKey, {
        data: categories,
        timestamp: Date.now()
      });

      return categories;
    } catch (error) {
      console.error('Failed to fetch product categories:', error);

      // Return fallback categories if API fails
      return this.getFallbackCategories(intent);
    }
  }

  /**
   * Search products by name or category
   */
  /**
   * Helper to parse colors safely
   */
  private parseColors(colors: any): string[] {
    if (Array.isArray(colors)) return colors;
    if (typeof colors === 'string') {
      // Handle comma-separated string or JSON string
      if (colors.startsWith('[') && colors.endsWith(']')) {
        try {
          const parsed = JSON.parse(colors);
          return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
          return [];
        }
      }
      return colors.split(',').map(c => c.trim()).filter(c => c.length > 0);
    }
    return [];
  }

  /**
   * Search products by name or category
   */
  async searchProducts(query: string, categoryId?: string): Promise<BusinessType[]> {
    try {
      const params = new URLSearchParams({ query });
      if (categoryId) {
        params.append('category_id', categoryId);
      }

      const response = await axios.get(`${this.baseUrl}/search?${params}`);

      if (response.data.success) {
        return response.data.data.map((product: any) => ({
          id: product.id,
          name: product.name,
          basePrice: product.base_price,
          image_url: product.image_url,
          colors: this.parseColors(product.colors),
          scales: product.package_sizes.map((size: any) => ({
            id: size.id,
            name: size.name,
            multiplier: size.multiplier,
            custom_price: size.custom_price,
          })),
          tenure: 24,
        }));
      }
      return [];
    } catch (error) {
      console.error('Failed to search products:', error);
      return [];
    }
  }

  /**
   * Get a specific product by ID
   */
  async getProduct(productId: number): Promise<BusinessType | null> {
    try {
      const response = await axios.get(`${this.baseUrl}/product/${productId}`);

      if (response.data.success) {
        const product = response.data.data;
        return {
          id: product.id,
          name: product.name,
          basePrice: product.base_price,
          image_url: product.image_url,
          colors: this.parseColors(product.colors),
          scales: product.package_sizes.map((size: any) => ({
            id: size.id,
            name: size.name,
            multiplier: size.multiplier,
            custom_price: size.custom_price,
          })),
          tenure: 24,
        };
      }

      return null;
    } catch (error) {
      console.error('Failed to fetch product:', error);
      return null;
    }
  }

  /**
   * Get products by category
   */
  async getProductsByCategory(categoryId: number): Promise<BusinessType[]> {
    try {
      const response = await axios.get(`${this.baseUrl}/category/${categoryId}`);

      if (response.data.success) {
        return response.data.data.map((product: any) => ({
          id: product.id,
          name: product.name,
          basePrice: product.base_price,
          image_url: product.image_url,
          colors: this.parseColors(product.colors),
          scales: product.package_sizes.map((size: any) => ({
            id: size.id,
            name: size.name,
            multiplier: size.multiplier,
            custom_price: size.custom_price,
          })),
          tenure: 24,
        }));
      }

      return [];
    } catch (error) {
      console.error('Failed to fetch products by category:', error);
      return [];
    }
  }

  /**
   * Calculate credit term options
   */
  getCreditTermOptions(amount: number): CreditTermOption[] {
    // Generate terms from 3 to 18 months
    const terms = Array.from({ length: 16 }, (_, i) => i + 3); // [3, 4, 5, ..., 18]
    const interestRate = 0.96; // 96% annual interest rate

    return terms.map(months => {
      // Calculate monthly payment using amortization formula
      const monthlyInterestRate = interestRate / 12;
      const monthlyPayment = amount > 0
        ? (amount * monthlyInterestRate * Math.pow(1 + monthlyInterestRate, months)) /
        (Math.pow(1 + monthlyInterestRate, months) - 1)
        : 0;

      return {
        months,
        monthlyPayment: Math.round(monthlyPayment * 100) / 100 // Round to 2 decimal places
      };
    });
  }

  /**
   * Clear cache (useful for admin updates)
   */
  clearCache(): void {
    this.cache.clear();
  }

  /**
   * Fallback categories if API fails
   * @param intent - Optional intent type to filter fallback categories
   */
  private getFallbackCategories(intent?: string): Category[] {
    const allCategories: Category[] = [
      // Personal Products Categories (for hirePurchase)
      {
        id: 'electronics',
        name: 'Electronics',
        emoji: '📱',
        subcategories: [
          {
            name: 'Mobile Phones',
            businesses: [
              {
                name: 'Smartphone',
                basePrice: 200,
                scales: [
                  { name: '1 Unit', multiplier: 1 },
                  { name: '2 Units', multiplier: 2 }
                ]
              }
            ]
          },
          {
            name: 'Laptops',
            businesses: [
              {
                name: 'Standard Laptop',
                basePrice: 500,
                scales: [
                  { name: '1 Unit', multiplier: 1 }
                ]
              }
            ]
          }
        ]
      },
      {
        id: 'appliances',
        name: 'Home Appliances',
        emoji: '🏠',
        subcategories: [
          {
            name: 'Refrigerators',
            businesses: [
              {
                name: 'Refrigerator',
                basePrice: 600,
                scales: [
                  { name: '1 Unit', multiplier: 1 }
                ]
              }
            ]
          }
        ]
      },
      {
        id: 'furniture',
        name: 'Furniture',
        emoji: '🛋️',
        subcategories: [
          {
            name: 'Living Room',
            businesses: [
              {
                name: 'Sofa Set',
                basePrice: 400,
                scales: [
                  { name: '3-Seater', multiplier: 1 },
                  { name: '5-Seater', multiplier: 1.5 }
                ]
              }
            ]
          }
        ]
      },
      {
        id: 'solar',
        name: 'Solar Systems',
        emoji: '☀️',
        subcategories: [
          {
            name: 'Power Stations',
            businesses: [
              {
                name: 'Solar Power Station',
                basePrice: 1000,
                scales: [
                  { name: '1kW', multiplier: 1 },
                  { name: '2kW', multiplier: 2 },
                  { name: '5kW', multiplier: 5 }
                ]
              }
            ]
          }
        ]
      },
      // MicroBiz Categories
      {
        id: 'agriculture',
        name: 'Agriculture',
        emoji: '🌾',
        subcategories: [
          {
            name: 'Cash Crops',
            businesses: [
              {
                name: 'Cotton',
                basePrice: 800,
                scales: [
                  { name: '1 Ha', multiplier: 1 },
                  { name: '2 Ha', multiplier: 2 },
                  { name: '3 Ha', multiplier: 3 },
                  { name: '5 Ha', multiplier: 5 }
                ]
              }
            ]
          }
        ]
      },
      {
        id: 'catering',
        name: 'Catering',
        emoji: '🍽️',
        subcategories: [
          {
            name: 'Food Services',
            businesses: [
              {
                name: 'Baking – Bread',
                basePrice: 1000,
                scales: [
                  { name: 'Small', multiplier: 1 },
                  { name: 'Medium', multiplier: 2 },
                  { name: 'Large', multiplier: 3 }
                ]
              }
            ]
          }
        ]
      }
    ];

    // Filter based on intent
    if (intent === 'hirePurchase') {
      return allCategories.filter(cat =>
        ['electronics', 'appliances', 'furniture', 'solar', 'technology'].includes(cat.id.toLowerCase())
      );
    } else if (intent === 'microBiz') {
      return allCategories.filter(cat =>
        !['electronics', 'appliances', 'furniture', 'solar', 'technology'].includes(cat.id.toLowerCase())
      );
    }

    return allCategories;
  }

  /**
   * Get statistics about the product catalog
   */
  async getStatistics() {
    try {
      const response = await axios.get(`${this.baseUrl}/statistics`);
      return response.data;
    } catch (error) {
      console.error('Failed to fetch product statistics:', error);
      return null;
    }
  }
}

// Export singleton instance
export const productService = new ProductService();

// Export the getCreditTermOptions function for backward compatibility
export const getCreditTermOptions = (amount: number) => {
  return productService.getCreditTermOptions(amount);
};
