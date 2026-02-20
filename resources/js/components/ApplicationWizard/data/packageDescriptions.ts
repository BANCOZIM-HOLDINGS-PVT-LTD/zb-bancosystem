export const packageDescriptions: Record<string, Record<string, string>> = {
    "Zimparks Vacation Package": {
        "1 Package": "2 Nights Accommodation, 1 Activity, Breakfast & Dinner",
        "2 Packages": "4 Nights Accommodation, 2 Activities, Breakfast & Dinner",
        "3 Packages": "6 Nights Accommodation, 3 Activities, Breakfast & Dinner"
    },
    // Poultry / Broiler Production
    "Broiler Production": {
        "Lite Package": "50 Birds, Starters, Grower and Finisher, Equipment. 30% Margin.",
        "Standard Package": "100 Birds, Starters, Grower and Finisher, Equipment. 30% Margin.",
        "Full House Package": "100 Birds, Starters, Grower and Finisher, Equipment, Housing. 30% Margin.",
        "Gold Package": "200 Birds, Starters, Grower and Finisher, Equipment, Housing. 30% Margin."
    },
    "Layers": {
        "Lite Package": "50 Birds, Starters, Grower and Layer Mash, Equipment. 40% Margin.",
        "Standard Package": "100 Birds, Starters, Grower and Layer Mash, Equipment. 40% Margin.",
        "Full House Package": "100 Birds, Starters, Grower and Layer Mash, Equipment, Housing. 40% Margin.",
        "Gold Package": "200 Birds, Starters, Grower and Layer Mash, Equipment, Housing. 40% Margin."
    },

    // Crop Production
    "Cash crop production": {
        "1 Ha": "Seed, Fertilizer, Chemicals for 1 Hectare",
        "2 Ha": "Seed, Fertilizer, Chemicals for 2 Hectares",
        "3 Ha": "Seed, Fertilizer, Chemicals for 3 Hectares",
        "5 Ha": "Seed, Fertilizer, Chemicals for 5 Hectares"
    },
    "Maize": {
        "1 Ha": "Maize Seed (25kg), Basal Fertilizer (300kg), Top Dressing (300kg), Herbicides",
        "2 Ha": "Maize Seed (50kg), Basal Fertilizer (600kg), Top Dressing (600kg), Herbicides",
        "3 Ha": "Maize Seed (75kg), Basal Fertilizer (900kg), Top Dressing (900kg), Herbicides",
        "5 Ha": "Maize Seed (125kg), Basal Fertilizer (1500kg), Top Dressing (1500kg), Herbicides"
    },
    // Default fallback for others
    "default": {
        "default": "Standard package equipment and materials."
    }
};

export const getPackageDescription = (productName: string, scaleName: string): string => {
    const product = packageDescriptions[productName];
    if (product) {
        return product[scaleName] || product["default"] || "Standard package details.";
    }
    // Try to find partial match or return default
    return "Includes necessary equipment and materials for this package size.";
};
