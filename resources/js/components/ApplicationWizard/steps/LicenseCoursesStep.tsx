import React, { useState } from 'react';
import { ChevronLeft, ChevronRight, Car, GraduationCap, MapPin, Award, Info, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface LicenseCoursesStepProps {
    data: any;
    onNext: (data: any) => void;
    onBack: () => void;
}

// License types
const LICENSE_TYPES = [
    { id: 'provisional', name: 'Provisional', description: 'Provisional driving permit', price: 10, priceUnit: '/week' },
    { id: 'learners', name: "Driver's Learners License", description: 'Full learners license course', price: 0, priceUnit: '' },
    { id: 'combo', name: 'Combo (Provisional + Drivers)', description: 'Complete package with provisional and full license', price: 10, priceUnit: '/week + lessons' },
];

// Experience levels
const EXPERIENCE_LEVELS = [
    { id: 'beginner', name: 'Beginner Learner Driver', lessons: 30, description: 'For first-time drivers' },
    { id: 'intermediate', name: 'Intermediate', lessons: 15, description: 'Some driving experience' },
    { id: 'advanced', name: 'Advanced', lessons: 10, description: 'Experienced, need refresher' },
];

// License classes with vehicle types and requirements
const LICENSE_CLASSES = [
    {
        id: 'class1',
        name: 'Class 1',
        pricePerLesson: 8,
        description: 'Buses, passenger coaches, and any other vehicle type',
        requirements: 'Min. 25 years, 5+ years Class 2/4 experience, defensive driving cert, medical cert',
        newCodes: ['D', 'G', 'P'],
        examples: 'Buses, coaches, most comprehensive license'
    },
    {
        id: 'class2',
        name: 'Class 2',
        pricePerLesson: 7,
        description: 'Heavy Commercial Vehicles (trucks over 2300kg)',
        requirements: 'Min. 18 years, with full Class 4 license',
        newCodes: ['C', 'BE', 'CE', 'DE'],
        examples: 'Heavy trucks, vehicles with two trailers'
    },
    {
        id: 'class3',
        name: 'Class 3',
        pricePerLesson: 4,
        description: 'Motorcycles',
        requirements: 'Min. 16 years (SADC system)',
        newCodes: ['A'],
        examples: 'All motorcycles'
    },
    {
        id: 'class4',
        name: 'Class 4',
        pricePerLesson: 4,
        description: 'Light motor vehicles (cars, mini-buses, taxis under 2300kg)',
        requirements: 'Min. 16 years (SADC system)',
        newCodes: ['B'],
        examples: 'Cars, mini-buses, taxis'
    },
    {
        id: 'class5',
        name: 'Class 5',
        pricePerLesson: 4,
        description: 'Tractors and construction/industrial machinery',
        requirements: 'Min. 18 years',
        newCodes: [],
        examples: 'Tractors, construction equipment, industrial machinery'
    },
];

// EasyGo Office Locations
const LESSON_LOCATIONS = [
    { id: 'harare-hq', name: 'Harare CBD' },
    { id: 'bulawayo', name: 'Bulawayo' },
    { id: 'mutare', name: 'Mutare' },
    { id: 'gweru', name: 'Gweru' },
    { id: 'bindura', name: 'Bindura' },
    { id: 'beitbridge', name: 'Beitbridge' },
    { id: 'chitungwiza', name: 'Chitungwiza' },
    { id: 'marondera', name: 'Marondera' },
    { id: 'masvingo', name: 'Masvingo' },
    { id: 'chinhoyi', name: 'Chinhoyi' },
    { id: 'victoria-falls', name: 'Victoria Falls' },
    { id: 'gwanda', name: 'Gwanda' },
];

const LicenseCoursesStep: React.FC<LicenseCoursesStepProps> = ({ data, onNext, onBack }) => {
    const [licenseType, setLicenseType] = useState<string>(data.licenseType || '');
    const [experienceLevel, setExperienceLevel] = useState<string>(data.experienceLevel || '');
    const [lessonLocation, setLessonLocation] = useState<string>(data.lessonLocation || '');
    const [licenseClass, setLicenseClass] = useState<string>(data.licenseClass || '');

    const selectedLicenseType = LICENSE_TYPES.find(t => t.id === licenseType);
    const selectedExperience = EXPERIENCE_LEVELS.find(e => e.id === experienceLevel);
    const selectedClass = LICENSE_CLASSES.find(c => c.id === licenseClass);

    // Cost constants
    const PROVISIONAL_LESSONS_PER_WEEK = 10; // $10/week
    const PROVISIONAL_TEST_FEE = 20; // $20 per test
    const COMBO_TEST_TRIES = 2; // Maximum 2 test attempts for combo

    // Calculate total cost based on license type
    const calculateTotalCost = () => {
        if (licenseType === 'provisional') {
            // Provisional only: 1 week lessons + 1 test = $10 + $20 = $30
            return PROVISIONAL_LESSONS_PER_WEEK + PROVISIONAL_TEST_FEE;
        }

        if (licenseType === 'learners') {
            // Learners only (already have provisional): Just lesson costs
            if (!selectedExperience || !selectedClass) return 0;
            return selectedExperience.lessons * selectedClass.pricePerLesson;
        }

        if (licenseType === 'combo') {
            // Combo: Provisional ($10 + $40 for 2 tests) + lessons
            if (!selectedExperience || !selectedClass) return 0;
            const provisionalCost = PROVISIONAL_LESSONS_PER_WEEK + (PROVISIONAL_TEST_FEE * COMBO_TEST_TRIES);
            const lessonCost = selectedExperience.lessons * selectedClass.pricePerLesson;
            return provisionalCost + lessonCost;
        }

        return 0;
    };

    const totalCost = calculateTotalCost();

    const canProceed = licenseType &&
        (licenseType === 'provisional' || (experienceLevel && lessonLocation && licenseClass));

    const handleContinue = () => {
        if (!canProceed) {
            alert('Please complete all selections.');
            return;
        }

        onNext({
            licenseType,
            licenseTypeName: selectedLicenseType?.name,
            experienceLevel,
            experienceLevelName: selectedExperience?.name,
            lessonsCount: selectedExperience?.lessons || 0,
            lessonLocation,
            lessonLocationName: LESSON_LOCATIONS.find(l => l.id === lessonLocation)?.name,
            licenseClass,
            licenseClassName: selectedClass?.name,
            pricePerLesson: selectedClass?.pricePerLesson || 0,
            licenseCoursesCost: totalCost,
        });
    };

    return (
        <div className="space-y-6 sm:space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-24 sm:pb-8">
            <div className="text-center">
                <h2 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">License Courses</h2>
                <p className="mt-2 text-sm sm:text-base text-gray-600 dark:text-gray-400">Select your driving license options.</p>
            </div>

            {/* 1. License Type */}
            <div className="space-y-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <Car className="w-5 h-5 text-emerald-600" />
                    1. Which license do you want to acquire?
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {LICENSE_TYPES.map((type) => (
                        <button
                            key={type.id}
                            onClick={() => setLicenseType(type.id)}
                            className={`p-4 rounded-xl border-2 transition-all text-left ${licenseType === type.id
                                ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-800 hover:border-emerald-300'
                                }`}
                        >
                            <div className="flex items-center gap-2 mb-2">
                                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${licenseType === type.id ? 'border-emerald-600' : 'border-gray-400'
                                    }`}>
                                    {licenseType === type.id && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                </div>
                                <span className="font-medium text-gray-900 dark:text-white">{type.name}</span>
                            </div>
                            <p className="text-sm text-gray-500">{type.description}</p>
                            {type.price > 0 && (
                                <p className="text-sm font-semibold text-emerald-600 mt-1">${type.price}{type.priceUnit}</p>
                            )}
                        </button>
                    ))}
                </div>
            </div>

            {/* Only show remaining steps if not just provisional */}
            {licenseType && licenseType !== 'provisional' && (
                <>
                    {/* 2. Experience Level */}
                    <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <GraduationCap className="w-5 h-5 text-emerald-600" />
                            2. Select your experience level
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {EXPERIENCE_LEVELS.map((level) => (
                                <button
                                    key={level.id}
                                    onClick={() => setExperienceLevel(level.id)}
                                    className={`p-4 rounded-xl border-2 transition-all text-left ${experienceLevel === level.id
                                        ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-gray-200 dark:border-gray-800 hover:border-emerald-300'
                                        }`}
                                >
                                    <div className="flex items-center gap-2 mb-2">
                                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${experienceLevel === level.id ? 'border-emerald-600' : 'border-gray-400'
                                            }`}>
                                            {experienceLevel === level.id && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                        </div>
                                        <span className="font-medium text-gray-900 dark:text-white">{level.name}</span>
                                    </div>
                                    <p className="text-sm text-gray-500">{level.description}</p>
                                    <p className="text-lg font-bold text-blue-600 mt-2">{level.lessons} lessons</p>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* 3. Lesson Location */}
                    {experienceLevel && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <MapPin className="w-5 h-5 text-emerald-600" />
                                3. Where do you want to conduct lessons?
                            </h3>
                            <select
                                value={lessonLocation}
                                onChange={(e) => setLessonLocation(e.target.value)}
                                className="w-full max-w-md px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">Select location</option>
                                {LESSON_LOCATIONS.map((loc) => (
                                    <option key={loc.id} value={loc.id}>{loc.name}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    {/* 4. License Class */}
                    {lessonLocation && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-300">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <Award className="w-5 h-5 text-emerald-600" />
                                4. Which license class do you want to obtain?
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {LICENSE_CLASSES.map((cls) => (
                                    <button
                                        key={cls.id}
                                        onClick={() => setLicenseClass(cls.id)}
                                        className={`p-4 rounded-xl border-2 transition-all text-left ${licenseClass === cls.id
                                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-900/20'
                                            : 'border-gray-200 dark:border-gray-800 hover:border-emerald-300'
                                            }`}
                                    >
                                        <div className="flex items-center justify-between mb-2">
                                            <div className="flex items-center gap-2">
                                                <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${licenseClass === cls.id ? 'border-emerald-600' : 'border-gray-400'
                                                    }`}>
                                                    {licenseClass === cls.id && <div className="w-2.5 h-2.5 bg-emerald-600 rounded-full" />}
                                                </div>
                                                <span className="font-medium text-gray-900 dark:text-white">{cls.name}</span>
                                            </div>
                                            <span className="text-emerald-600 font-bold">${cls.pricePerLesson}/30min</span>
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">{cls.description}</p>
                                        <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">{cls.requirements}</p>
                                        {cls.newCodes.length > 0 && (
                                            <div className="mt-2 flex gap-1 flex-wrap">
                                                {cls.newCodes.map(code => (
                                                    <span key={code} className="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full">
                                                        Cat. {code}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                        <p className="text-xs text-gray-500 mt-2">{cls.examples}</p>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </>
            )}

            {/* Cost Summary */}
            {canProceed && (
                <Card className="p-6 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <h4 className="font-semibold text-emerald-900 dark:text-emerald-200 mb-4">Cost Summary</h4>
                    <div className="space-y-2">
                        {licenseType === 'provisional' && (
                            <>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Provisional Lessons (1 week)</span>
                                    <span>$10.00</span>
                                </div>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Provisional Test</span>
                                    <span>$20.00</span>
                                </div>
                                <div className="border-t border-emerald-200 dark:border-emerald-700 my-2 pt-2 flex justify-between font-bold text-emerald-900 dark:text-emerald-200">
                                    <span>Total</span>
                                    <span>$30.00</span>
                                </div>
                            </>
                        )}
                        {licenseType === 'learners' && (
                            <>
                                <p className="text-xs text-gray-500 mb-2">Assumes you already have a provisional license</p>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Lessons ({selectedExperience?.lessons} x ${selectedClass?.pricePerLesson})</span>
                                    <span>${(selectedExperience?.lessons || 0) * (selectedClass?.pricePerLesson || 0)}.00</span>
                                </div>
                                <div className="border-t border-emerald-200 dark:border-emerald-700 my-2 pt-2 flex justify-between font-bold text-emerald-900 dark:text-emerald-200">
                                    <span>Total</span>
                                    <span>${totalCost.toFixed(2)}</span>
                                </div>
                            </>
                        )}
                        {licenseType === 'combo' && (
                            <>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Provisional Lessons (1 week)</span>
                                    <span>$10.00</span>
                                </div>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Provisional Tests (2 attempts x $20)</span>
                                    <span>$40.00</span>
                                </div>
                                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>Driving Lessons ({selectedExperience?.lessons} x ${selectedClass?.pricePerLesson})</span>
                                    <span>${(selectedExperience?.lessons || 0) * (selectedClass?.pricePerLesson || 0)}.00</span>
                                </div>
                                <div className="border-t border-emerald-200 dark:border-emerald-700 my-2 pt-2 flex justify-between font-bold text-emerald-900 dark:text-emerald-200">
                                    <span>Total</span>
                                    <span>${totalCost.toFixed(2)}</span>
                                </div>
                            </>
                        )}
                    </div>
                </Card>
            )}

            {/* Info Box */}
            <div className="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
                <h4 className="flex items-center gap-2 font-medium text-blue-900 dark:text-blue-200 mb-2">
                    <Info className="w-4 h-4" />
                    License Class Information
                </h4>
                <div className="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                    <p><strong>Class 1:</strong> Buses, coaches, most comprehensive (25+ yrs, 5+ yrs exp)</p>
                    <p><strong>Class 2:</strong> Heavy trucks over 2300kg (18+ yrs, Class 4 required)</p>
                    <p><strong>Class 3:</strong> Motorcycles (16+ yrs)</p>
                    <p><strong>Class 4:</strong> Light vehicles under 2300kg - cars, taxis (16+ yrs)</p>
                    <p><strong>Class 5:</strong> Tractors, construction machinery (18+ yrs)</p>
                </div>
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-4 pt-6 mb-32">
                <Button variant="outline" onClick={onBack} className="flex items-center gap-2">
                    <ChevronLeft className="h-4 w-4" />
                    Back
                </Button>
                <Button
                    onClick={handleContinue}
                    disabled={!canProceed}
                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700"
                >
                    Continue
                    <ChevronRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
};

export default LicenseCoursesStep;
