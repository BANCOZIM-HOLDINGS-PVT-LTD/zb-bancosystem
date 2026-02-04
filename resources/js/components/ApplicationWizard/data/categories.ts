export interface BusinessType {
  name: string;
  basePrice: number;
  scales: {
    name: string;
    multiplier: number;
  }[];
  tenure?: number;
}

export interface Subcategory {
  name: string;
  businesses: BusinessType[];
}

export interface Category {
  id: string;
  name: string;
  emoji: string;
  subcategories: Subcategory[];
}

export const productCategories: Category[] = [
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
          },
          {
            name: 'Maize',
            basePrice: 800,
            scales: [
              { name: '1 Ha', multiplier: 1 },
              { name: '2 Ha', multiplier: 2 },
              { name: '3 Ha', multiplier: 3 },
              { name: '5 Ha', multiplier: 5 }
            ]
          },
          {
            name: 'Potato',
            basePrice: 800,
            scales: [
              { name: '1 Ha', multiplier: 1 },
              { name: '2 Ha', multiplier: 2 },
              { name: '3 Ha', multiplier: 3 },
              { name: '5 Ha', multiplier: 5 }
            ]
          },
          {
            name: 'Soya Beans',
            basePrice: 800,
            scales: [
              { name: '1 Ha', multiplier: 1 },
              { name: '2 Ha', multiplier: 2 },
              { name: '3 Ha', multiplier: 3 },
              { name: '5 Ha', multiplier: 5 }
            ]
          },
          {
            name: 'Sugar Beans',
            basePrice: 800,
            scales: [
              { name: '1 Ha', multiplier: 1 },
              { name: '2 Ha', multiplier: 2 },
              { name: '3 Ha', multiplier: 3 },
              { name: '5 Ha', multiplier: 5 }
            ]
          },
          {
            name: 'Sunflower',
            basePrice: 800,
            scales: [
              { name: '1 Ha', multiplier: 1 },
              { name: '2 Ha', multiplier: 2 },
              { name: '3 Ha', multiplier: 3 },
              { name: '5 Ha', multiplier: 5 }
            ]
          },
          {
            name: 'Sweet Potato',
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
    id: 'animal-husbandry',
    name: 'Animal Husbandry',
    emoji: '🐄',
    subcategories: [
      {
        name: 'Livestock & Poultry',
        businesses: [
          {
            name: 'Animal Feed Production',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Bee keeping',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Cattle Services',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Chickens Layers',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Chickens Rearing',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Goat Rearing',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Fish Farming',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Rabbits',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          },
          {
            name: 'Piggery',
            basePrice: 600,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 },
              { name: 'Commercial', multiplier: 5 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'business-licensing',
    name: 'Business Licensing',
    emoji: '📜',
    subcategories: [
      {
        name: 'Licenses',
        businesses: [
          {
            name: 'Liquor Store License',
            basePrice: 300,
            scales: [
              { name: 'Standard License', multiplier: 1 }
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
          },
          {
            name: 'Baking - Cakes & confectionery',
            basePrice: 1000,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 }
            ]
          },
          {
            name: 'Chip Fryer',
            basePrice: 800,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 }
            ]
          },
          {
            name: 'Canteen',
            basePrice: 1200,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 }
            ]
          },
          {
            name: 'Mobile food kiosk',
            basePrice: 900,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 }
            ]
          },
          {
            name: 'Outside catering',
            basePrice: 1100,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 2 },
              { name: 'Large', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'construction',
    name: 'Building & Construction Equipment',
    emoji: '🔨',
    subcategories: [
      {
        name: 'Trade Tools',
        businesses: [
          {
            name: 'Electrical',
            basePrice: 1500,
            scales: [
              { name: 'Basic Tools', multiplier: 1 },
              { name: 'Standard Tools', multiplier: 2 },
              { name: 'Professional Tools', multiplier: 3 }
            ]
          },
          {
            name: 'Plumbing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Tools', multiplier: 1 },
              { name: 'Standard Tools', multiplier: 2 },
              { name: 'Professional Tools', multiplier: 3 }
            ]
          },
          {
            name: 'Tiling',
            basePrice: 1000,
            scales: [
              { name: 'Basic Tools', multiplier: 1 },
              { name: 'Standard Tools', multiplier: 2 },
              { name: 'Professional Tools', multiplier: 3 }
            ]
          },
          {
            name: 'Brickwork tools',
            basePrice: 800,
            scales: [
              { name: 'Basic Tools', multiplier: 1 },
              { name: 'Standard Tools', multiplier: 2 },
              { name: 'Professional Tools', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'entertainment',
    name: 'Entertainment',
    emoji: '🎵',
    subcategories: [
      {
        name: 'Entertainment Equipment',
        businesses: [
          {
            name: 'Musical Instruments Hire',
            basePrice: 2000,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 2 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'PA System',
            basePrice: 1800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 2 },
              { name: 'Professional Setup', multiplier: 3 }
            ]
          },
          {
            name: 'Snooker Table',
            basePrice: 3000,
            scales: [
              { name: 'Single Table', multiplier: 1 },
              { name: 'Multiple Tables', multiplier: 2 },
              { name: 'Full Hall', multiplier: 3 }
            ]
          },
          {
            name: 'Slug Table',
            basePrice: 2500,
            scales: [
              { name: 'Single Table', multiplier: 1 },
              { name: 'Multiple Tables', multiplier: 2 },
              { name: 'Full Hall', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'events-hire',
    name: 'Events Hire',
    emoji: '🎉',
    subcategories: [
      {
        name: 'Event Equipment',
        businesses: [
          {
            name: 'Chairs & table',
            basePrice: 1200,
            scales: [
              { name: 'Small Event', multiplier: 1 },
              { name: 'Medium Event', multiplier: 2 },
              { name: 'Large Event', multiplier: 3 }
            ]
          },
          {
            name: 'Tent',
            basePrice: 2000,
            scales: [
              { name: 'Small Tent', multiplier: 1 },
              { name: 'Medium Tent', multiplier: 2 },
              { name: 'Large Tent', multiplier: 3 }
            ]
          },
          {
            name: 'Decor',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 2 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'Red Carpet and accessories',
            basePrice: 800,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 2 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'Portable Toilet Hire',
            basePrice: 1000,
            scales: [
              { name: 'Basic Units', multiplier: 1 },
              { name: 'Standard Units', multiplier: 2 },
              { name: 'Premium Units', multiplier: 3 }
            ]
          },
          {
            name: 'Interactive Big Screen Monitors',
            basePrice: 3000,
            scales: [
              { name: 'Single Screen', multiplier: 1 },
              { name: 'Multiple Screens', multiplier: 2 },
              { name: 'Full Setup', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'hair-grooming',
    name: 'Hair & Grooming',
    emoji: '💇',
    subcategories: [
      {
        name: 'Beauty Services',
        businesses: [
          {
            name: 'Barber',
            basePrice: 400,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 2 },
              { name: 'Premium Setup', multiplier: 3 }
            ]
          },
          {
            name: 'Hair Salon',
            basePrice: 800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 2 },
              { name: 'Premium Setup', multiplier: 3 }
            ]
          },
          {
            name: 'Nail Installation',
            basePrice: 600,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 2 },
              { name: 'Premium Setup', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'home-industry-manufacturing',
    name: 'Home Industry Manufacturing',
    emoji: '🏭',
    subcategories: [
      {
        name: 'Manufacturing',
        businesses: [
          {
            name: 'Detergent Chemicals',
            basePrice: 1200,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3 }
            ]
          },
          {
            name: 'Fence Making',
            basePrice: 1500,
            scales: [
              { name: 'Basic Equipment', multiplier: 1 },
              { name: 'Standard Equipment', multiplier: 2 },
              { name: 'Industrial Equipment', multiplier: 3 }
            ]
          },
          {
            name: 'Furniture –Carpentry',
            basePrice: 1800,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 2 },
              { name: 'Full Workshop', multiplier: 3 }
            ]
          },
          {
            name: 'Soap production',
            basePrice: 1000,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3 }
            ]
          },
          {
            name: 'Ice Making',
            basePrice: 2000,
            scales: [
              { name: 'Small Production', multiplier: 1 },
              { name: 'Medium Production', multiplier: 2 },
              { name: 'Large Production', multiplier: 3 }
            ]
          },
          {
            name: 'Welding',
            basePrice: 1600,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 2 },
              { name: 'Professional Setup', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'farming-machinery',
    name: 'Farming Machinery',
    emoji: '🚜',
    subcategories: [
      {
        name: 'Agricultural Equipment',
        businesses: [
          {
            name: 'Egg Incubator',
            basePrice: 1500,
            scales: [
              { name: 'Small Capacity', multiplier: 1 },
              { name: 'Medium Capacity', multiplier: 2 },
              { name: 'Large Capacity', multiplier: 3 }
            ]
          },
          {
            name: 'Indigenous Agric Pack',
            basePrice: 2000,
            scales: [
              { name: 'Basic Pack', multiplier: 1 },
              { name: 'Standard Pack', multiplier: 2 },
              { name: 'Complete Pack', multiplier: 3 }
            ]
          },
          {
            name: 'Grinding Mill',
            basePrice: 3000,
            scales: [
              { name: 'Small Mill', multiplier: 1 },
              { name: 'Medium Mill', multiplier: 2 },
              { name: 'Industrial Mill', multiplier: 3 }
            ]
          },
          {
            name: 'Green House',
            basePrice: 2500,
            scales: [
              { name: 'Small Greenhouse', multiplier: 1 },
              { name: 'Medium Greenhouse', multiplier: 2 },
              { name: 'Large Greenhouse', multiplier: 3 }
            ]
          },
          {
            name: 'Farm Security',
            basePrice: 1800,
            scales: [
              { name: 'Basic Security', multiplier: 1 },
              { name: 'Standard Security', multiplier: 2 },
              { name: 'Advanced Security', multiplier: 3 }
            ]
          },
          {
            name: 'Micro Irrigation Systems',
            basePrice: 2200,
            scales: [
              { name: 'Small System', multiplier: 1 },
              { name: 'Medium System', multiplier: 2 },
              { name: 'Large System', multiplier: 3 }
            ]
          },
          {
            name: 'Tractors & accessories',
            basePrice: 15000,
            scales: [
              { name: 'Basic Tractor', multiplier: 1 },
              { name: 'Standard Tractor', multiplier: 1.5 },
              { name: 'Full Package', multiplier: 2 }
            ]
          },
          {
            name: 'Cooking Oil Production',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          },
          {
            name: 'Dry Food Repackaging',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          },
          {
            name: 'Freezit making',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          },
          {
            name: 'Maputi production',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          },
          {
            name: 'Peanut Butter Making',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          },
          {
            name: 'Roasted Corn/Peanuts',
            basePrice: 900,
            scales: [
              { name: 'Small Batch', multiplier: 1 },
              { name: 'Medium Batch', multiplier: 2 },
              { name: 'Large Batch', multiplier: 3.5 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'financial-services-agency',
    name: 'Financial Services Agency',
    emoji: '🏦',
    subcategories: [
      {
        name: 'Agency Services',
        businesses: [
          {
            name: 'Ecocash Agent',
            basePrice: 500,
            scales: [
              { name: 'Standard Agent', multiplier: 1 }
            ]
          },
          {
            name: 'ZB Bank Agent',
            basePrice: 500,
            scales: [
              { name: 'Standard Agent', multiplier: 1 }
            ]
          }
        ]
      }
    ]
  },

  {
    id: 'meat-processing',
    name: 'Meat Processing Equipment',
    emoji: '🥩',
    subcategories: [
      {
        name: 'Meat Services',
        businesses: [
          {
            name: 'Butchery',
            basePrice: 1800,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 1.8 },
              { name: 'Large Shop', multiplier: 3 }
            ]
          },
          {
            name: 'Meat Cutter',
            basePrice: 1800,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 1.8 },
              { name: 'Large Shop', multiplier: 3 }
            ]
          },
          {
            name: 'Mince Making',
            basePrice: 1800,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 1.8 },
              { name: 'Large Shop', multiplier: 3 }
            ]
          },
          {
            name: 'Sausage Production',
            basePrice: 1800,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 1.8 },
              { name: 'Large Shop', multiplier: 3 }
            ]
          },
          {
            name: 'Chicken Plucker',
            basePrice: 500,
            scales: [
              { name: 'Small', multiplier: 1 },
              { name: 'Medium', multiplier: 1.5 },
              { name: 'Large', multiplier: 2 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'mining',
    name: 'Small Scale Mining',
    emoji: '⛏️',
    subcategories: [
      {
        name: 'Mining Equipment',
        businesses: [
          {
            name: 'Water Extraction',
            basePrice: 5000,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 2 },
              { name: 'Heavy Duty', multiplier: 4 }
            ]
          },
          {
            name: 'Drilling',
            basePrice: 5000,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 2 },
              { name: 'Heavy Duty', multiplier: 4 }
            ]
          },
          {
            name: 'Industrial generators',
            basePrice: 5000,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 2 },
              { name: 'Heavy Duty', multiplier: 4 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'printing',
    name: 'Branding and Material Printing',
    emoji: '🖨️',
    subcategories: [
      {
        name: 'Printing Services',
        businesses: [
          {
            name: 'Mugs-Cup',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Vehicle Branding',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Digital Printing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'D.T.F Printing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Screen Printing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Plans printing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'T Shirt & Cap Printing',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Photocopy',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          },
          {
            name: 'Photo printing instant',
            basePrice: 1200,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.8 },
              { name: 'Professional Setup', multiplier: 3.2 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'professional-services-equipment',
    name: 'Professional Services Equipment',
    emoji: '💼',
    subcategories: [
      {
        name: 'Service Equipment',
        businesses: [
          {
            name: 'Bar',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Butchery',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Car key programming',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Cell repair',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Car wash',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Cleaning commercial service',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Internet',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Gaming',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Gas Station',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Grass Cutting',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Gymnasium',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Bike Delivery Service',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Laptop repairs',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Laundry Service',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Lock Smith Service',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Photography Studio',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Photo printing',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Pre School',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Satellite Dish Installation',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Saw Mill',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Shop Accessories',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Shop Fitting',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          },
          {
            name: 'Videography',
            basePrice: 1500,
            scales: [
              { name: 'Basic', multiplier: 1 },
              { name: 'Standard', multiplier: 1.8 },
              { name: 'Premium', multiplier: 2.8 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'retail-shops',
    name: 'Retail Shops',
    emoji: '🛍️',
    subcategories: [
      {
        name: 'Retail Businesses',
        businesses: [
          {
            name: 'Agro',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Book',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Candy and Confectionery Shop',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Cell phone',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Cellphone Accessories',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Ceramics Tiles',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Clothing (Men/Women: Formal/Informal)',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Cosmetics',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Hair (braids, wigs, weaves)',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Hats & Caps',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Herbal',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Fabric & textile',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Jewellery',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Shoes - Men\'s Sports',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Solar & Accessories',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Hardware',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Automotive Motor Spares',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'General Dealer (plastic ware, cango pots, comforters, mini hardware, protective clothing, blankets)',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          },
          {
            name: 'Stationery Shop',
            basePrice: 1000,
            scales: [
              { name: 'Small Shop', multiplier: 1 },
              { name: 'Medium Shop', multiplier: 2 },
              { name: 'Large Shop', multiplier: 3.5 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'tailoring',
    name: 'Tailoring',
    emoji: '✂️',
    subcategories: [
      {
        name: 'Tailoring Services',
        businesses: []
      }
    ]
  },
  {
    id: 'tailoring-machinery',
    name: 'Tailoring Machinery',
    emoji: '🧵',
    subcategories: [
      {
        name: 'Machinery',
        businesses: [
          {
            name: 'Embroidery Machine',
            basePrice: 800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.6 },
              { name: 'Industrial Setup', multiplier: 2.8 }
            ]
          },
          {
            name: 'Knitting Machine',
            basePrice: 800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.6 },
              { name: 'Industrial Setup', multiplier: 2.8 }
            ]
          },
          {
            name: 'Industrial Overlocker',
            basePrice: 800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.6 },
              { name: 'Industrial Setup', multiplier: 2.8 }
            ]
          },
          {
            name: 'Domestic Sewing Machine',
            basePrice: 800,
            scales: [
              { name: 'Basic Setup', multiplier: 1 },
              { name: 'Standard Setup', multiplier: 1.6 },
              { name: 'Industrial Setup', multiplier: 2.8 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'trade-services',
    name: 'Trade Services',
    emoji: '📱',
    subcategories: [
      {
        name: 'Trading Services',
        businesses: [
          {
            name: 'Airtime scratch cards distribution',
            basePrice: 600,
            scales: [
              { name: 'Small Scale', multiplier: 1 },
              { name: 'Medium Scale', multiplier: 1.8 },
              { name: 'Large Scale', multiplier: 3 }
            ]
          },
          {
            name: 'Grocery and Tuckshop',
            basePrice: 600,
            scales: [
              { name: 'Small Scale', multiplier: 1 },
              { name: 'Medium Scale', multiplier: 1.8 },
              { name: 'Large Scale', multiplier: 3 }
            ]
          },
          {
            name: 'Tyres',
            basePrice: 600,
            scales: [
              { name: 'Small Scale', multiplier: 1 },
              { name: 'Medium Scale', multiplier: 1.8 },
              { name: 'Large Scale', multiplier: 3 }
            ]
          },
          {
            name: 'Windscreen Replacement',
            basePrice: 600,
            scales: [
              { name: 'Small Scale', multiplier: 1 },
              { name: 'Medium Scale', multiplier: 1.8 },
              { name: 'Large Scale', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'vehicle',
    name: 'Motor Vehicle Specialised Services',
    emoji: '🚗',
    subcategories: [
      {
        name: 'Vehicle Services',
        businesses: [
          {
            name: 'Air con re-gassing',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          },
          {
            name: 'Tyre Fitting',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          },
          {
            name: 'Vehicle Alignment',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          },
          {
            name: 'Vehicle diagnosis',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          },
          {
            name: 'Vehicle Repairs Workshop',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          },
          {
            name: 'Vehicle panel beating',
            basePrice: 2000,
            scales: [
              { name: 'Basic Workshop', multiplier: 1 },
              { name: 'Standard Workshop', multiplier: 1.8 },
              { name: 'Full Service', multiplier: 3.2 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'vocation',
    name: 'Vocation',
    emoji: '🎓',
    subcategories: [
      {
        name: 'Vocational Services',
        businesses: [
          {
            name: 'Network marketing admission Fees (Avon, B.B.B, Forever Living, Honey)',
            basePrice: 300,
            scales: [
              { name: 'Individual', multiplier: 1 },
              { name: 'Small Group', multiplier: 2 },
              { name: 'Large Group', multiplier: 4 }
            ]
          },
          {
            name: 'Nurse Aid',
            basePrice: 300,
            scales: [
              { name: 'Individual', multiplier: 1 },
              { name: 'Small Group', multiplier: 2 },
              { name: 'Large Group', multiplier: 4 }
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'wedding-attire-hire',
    name: 'Wedding Attire Hire',
    emoji: '💒',
    subcategories: [
      {
        name: 'Wedding Services',
        businesses: [
          {
            name: 'Accessories ( Artificial Flowers, Crown, Ring Basset)',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 1.8 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'Bridal gown',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 1.8 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'Bridal team',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 1.8 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'Groom suit',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 1.8 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          },
          {
            name: 'High Back Chair',
            basePrice: 1500,
            scales: [
              { name: 'Basic Package', multiplier: 1 },
              { name: 'Standard Package', multiplier: 1.8 },
              { name: 'Premium Package', multiplier: 3 }
            ]
          }
        ]
      }
    ]
  }
];

export const getCreditTermOptions = (amount: number) => {
  const terms = [6, 12, 18, 24, 36];
  return terms.map(months => ({
    months,
    monthlyPayment: Math.round(amount / months * 1.1) // 10% interest approximation
  }));
};