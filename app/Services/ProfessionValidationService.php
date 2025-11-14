<?php

namespace App\Services;

class ProfessionValidationService
{
    /**
     * Zimbabwe Government Ministries and their associated professions
     * As per note 38: Validate professions against ministries
     */
    protected static $ministryProfessions = [
        'Education, Sport, Arts and Culture' => [
            'Teacher',
            'Lecturer',
            'Professor',
            'Principal',
            'Headmaster/Headmistress',
            'Education Officer',
            'Curriculum Developer',
            'School Inspector',
            'Sports Officer',
            'Arts Officer',
            'Cultural Officer',
            'Librarian',
            'Academic Administrator',
            'Research Officer',
            'Early Childhood Development Practitioner',
        ],

        'Health and Child Care' => [
            'Doctor',
            'Nurse',
            'Pharmacist',
            'Dentist',
            'Physiotherapist',
            'Radiographer',
            'Laboratory Technician',
            'Health Inspector',
            'Mental Health Practitioner',
            'Nutritionist',
            'Public Health Officer',
            'Midwife',
            'Optometrist',
            'Medical Technologist',
            'Community Health Worker',
            'Environmental Health Officer',
        ],

        'Home Affairs and Cultural Heritage' => [
            'Immigration Officer',
            'Civil Servant',
            'Registrar',
            'Archives Officer',
            'Museum Curator',
            'Heritage Officer',
            'Border Control Officer',
            'Identity Document Officer',
            'Citizenship Officer',
            'Cultural Preservation Officer',
        ],

        'Justice, Legal and Parliamentary Affairs' => [
            'Lawyer',
            'Attorney',
            'Magistrate',
            'Judge',
            'Court Clerk',
            'Legal Officer',
            'Prosecutor',
            'Legal Assistant',
            'Paralegal',
            'Court Reporter',
            'Sheriff',
            'Parliamentary Officer',
            'Legislative Drafter',
        ],

        'Finance and Economic Development' => [
            'Accountant',
            'Auditor',
            'Financial Analyst',
            'Economist',
            'Tax Officer',
            'Revenue Officer',
            'Banking Officer',
            'Investment Officer',
            'Budget Analyst',
            'Financial Planner',
            'Treasury Officer',
            'Insurance Officer',
            'Actuarial Scientist',
        ],

        'Lands, Agriculture, Fisheries, Water and Rural Development' => [
            'Agricultural Officer',
            'Veterinarian',
            'Veterinary Technician',
            'Animal Health Technician',
            'Crop Specialist',
            'Livestock Officer',
            'Irrigation Engineer',
            'Water Engineer',
            'Rural Development Officer',
            'Land Surveyor',
            'Fisheries Officer',
            'Agricultural Economist',
            'Extension Officer',
            'Food Scientist',
        ],

        'Energy and Power Development' => [
            'Electrical Engineer',
            'Power Engineer',
            'Energy Analyst',
            'Power Systems Engineer',
            'Renewable Energy Specialist',
            'Energy Auditor',
            'Grid Operator',
            'Power Plant Operator',
            'Energy Consultant',
            'Electrical Technician',
        ],

        'Transport and Infrastructural Development' => [
            'Civil Engineer',
            'Transport Planner',
            'Road Engineer',
            'Bridge Engineer',
            'Construction Manager',
            'Project Manager',
            'Architect',
            'Quantity Surveyor',
            'Traffic Engineer',
            'Infrastructure Analyst',
            'Transportation Officer',
            'Construction Technician',
        ],

        'Information Communication Technology, Postal and Courier Services' => [
            'Software Developer',
            'Systems Administrator',
            'Database Administrator',
            'Network Administrator',
            'IT Support Specialist',
            'Cybersecurity Specialist',
            'Data Analyst',
            'Web Developer',
            'IT Project Manager',
            'Telecommunications Engineer',
            'ICT Officer',
            'Digital Marketing Specialist',
            'IT Consultant',
        ],

        'Public Service, Labour and Social Welfare' => [
            'Social Worker',
            'Human Resources Officer',
            'Labour Officer',
            'Welfare Officer',
            'Public Service Commissioner',
            'Employment Officer',
            'Community Development Officer',
            'Training Officer',
            'Industrial Relations Officer',
            'Occupational Safety Officer',
        ],

        'Higher and Tertiary Education, Innovation, Science and Technology Development' => [
            'Research Scientist',
            'University Lecturer',
            'Innovation Officer',
            'Technology Transfer Officer',
            'Research Coordinator',
            'Laboratory Manager',
            'Science Officer',
            'Technology Developer',
            'Patent Officer',
            'Academic Researcher',
        ],

        'Environment, Climate, Tourism and Hospitality Industry' => [
            'Environmental Officer',
            'Climate Change Officer',
            'Tourism Officer',
            'Hotel Manager',
            'Tour Guide',
            'Conservation Officer',
            'Environmental Scientist',
            'Tourism Planner',
            'Hospitality Manager',
            'Park Ranger',
            'Wildlife Officer',
            'Meteorologist',
        ],

        'Industry and Commerce' => [
            'Industrial Engineer',
            'Production Manager',
            'Quality Control Officer',
            'Manufacturing Engineer',
            'Business Development Officer',
            'Trade Officer',
            'Industrial Relations Officer',
            'Standards Officer',
            'Export Officer',
            'Import Officer',
        ],

        'Mines and Mining Development' => [
            'Mining Engineer',
            'Geologist',
            'Metallurgist',
            'Mine Safety Officer',
            'Mining Technician',
            'Exploration Geologist',
            'Mine Surveyor',
            'Minerals Processing Engineer',
            'Environmental Mining Officer',
        ],

        'Defence and War Veterans Affairs' => [
            'Military Officer',
            'Defence Analyst',
            'Security Officer',
            'Intelligence Officer',
            'War Veterans Officer',
            'Defence Procurement Officer',
            'Military Engineer',
            'Defence Contractor',
        ],

        'National Housing and Social Amenities' => [
            'Housing Officer',
            'Urban Planner',
            'Housing Development Officer',
            'Social Amenities Officer',
            'Community Planner',
            'Housing Policy Officer',
            'Development Officer',
        ],

        'Women Affairs, Community, Small and Medium Enterprises Development' => [
            'Gender Officer',
            'Women Empowerment Officer',
            'SME Development Officer',
            'Community Development Officer',
            'Microfinance Officer',
            'Entrepreneurship Officer',
            'Cooperative Officer',
        ],

        'Youth, Sport, Arts and Recreation' => [
            'Youth Officer',
            'Sports Coordinator',
            'Recreation Officer',
            'Youth Development Officer',
            'Sports Administrator',
            'Arts Coordinator',
            'Youth Counsellor',
        ],
    ];

    /**
     * Additional professions not directly under specific ministries
     */
    protected static $generalProfessions = [
        'Business Owner',
        'Entrepreneur',
        'Consultant',
        'Freelancer',
        'Contractor',
        'Farmer',
        'Trader',
        'Mechanic',
        'Electrician',
        'Plumber',
        'Carpenter',
        'Mason',
        'Driver',
        'Security Guard',
        'Cleaner',
        'Caretaker',
        'Sales Representative',
        'Marketing Officer',
        'Customer Service Representative',
        'Administrative Assistant',
        'Secretary',
        'Receptionist',
        'Cashier',
        'Shop Attendant',
        'Waiter/Waitress',
        'Chef',
        'Cook',
        'Hairdresser',
        'Tailor',
        'Seamstress',
        'Artist',
        'Musician',
        'Photographer',
        'Journalist',
        'Writer',
        'Editor',
        'Translator',
        'Pastor',
        'Priest',
        'Religious Leader',
        'Traditional Healer',
        'Other',
    ];

    /**
     * Get all valid professions
     */
    public static function getAllProfessions(): array
    {
        $allProfessions = [];

        // Add ministry professions
        foreach (self::$ministryProfessions as $ministry => $professions) {
            $allProfessions = array_merge($allProfessions, $professions);
        }

        // Add general professions
        $allProfessions = array_merge($allProfessions, self::$generalProfessions);

        // Remove duplicates and sort
        $allProfessions = array_unique($allProfessions);
        sort($allProfessions);

        return $allProfessions;
    }

    /**
     * Validate if a profession is acceptable
     */
    public static function validateProfession(string $profession): bool
    {
        $allProfessions = self::getAllProfessions();

        // Exact match
        if (in_array($profession, $allProfessions)) {
            return true;
        }

        // Case-insensitive match
        $lowerProfession = strtolower($profession);
        foreach ($allProfessions as $validProfession) {
            if (strtolower($validProfession) === $lowerProfession) {
                return true;
            }
        }

        // Partial match for flexibility
        foreach ($allProfessions as $validProfession) {
            if (stripos($validProfession, $profession) !== false ||
                stripos($profession, $validProfession) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get ministry for a given profession
     */
    public static function getMinistryForProfession(string $profession): ?string
    {
        foreach (self::$ministryProfessions as $ministry => $professions) {
            foreach ($professions as $validProfession) {
                if (strtolower($profession) === strtolower($validProfession) ||
                    stripos($validProfession, $profession) !== false ||
                    stripos($profession, $validProfession) !== false) {
                    return $ministry;
                }
            }
        }

        return null; // General profession or not found
    }

    /**
     * Get professions by ministry
     */
    public static function getProfessionsByMinistry(string $ministry): array
    {
        return self::$ministryProfessions[$ministry] ?? [];
    }

    /**
     * Get all ministries
     */
    public static function getAllMinistries(): array
    {
        return array_keys(self::$ministryProfessions);
    }

    /**
     * Get profession suggestions based on partial input
     */
    public static function suggestProfessions(string $input, int $limit = 10): array
    {
        $input = strtolower($input);
        $allProfessions = self::getAllProfessions();
        $suggestions = [];

        // First pass: exact word matches
        foreach ($allProfessions as $profession) {
            if (stripos($profession, $input) === 0) {
                $suggestions[] = $profession;
            }
        }

        // Second pass: contains matches
        if (count($suggestions) < $limit) {
            foreach ($allProfessions as $profession) {
                if (stripos($profession, $input) !== false && ! in_array($profession, $suggestions)) {
                    $suggestions[] = $profession;
                }
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get formatted profession list for forms
     */
    public static function getFormattedProfessionList(): array
    {
        $allProfessions = self::getAllProfessions();
        $formatted = [];

        foreach ($allProfessions as $profession) {
            $formatted[] = [
                'value' => $profession,
                'label' => $profession,
                'ministry' => self::getMinistryForProfession($profession),
            ];
        }

        return $formatted;
    }
}
