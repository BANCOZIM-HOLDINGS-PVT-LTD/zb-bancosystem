import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Globe, ChevronRight } from 'lucide-react';

interface LanguageSelectionProps {
    data: any;
    onNext: (data: any) => void;
    loading?: boolean;
}

const languages = [
    { code: 'en', name: 'English', greeting: 'Welcome' },
    { code: 'sn', name: 'Shona', greeting: 'Mauya' },
    { code: 'nd', name: 'Ndebele', greeting: 'Ngiyakwemukela' }
];

const LanguageSelection: React.FC<LanguageSelectionProps> = ({ onNext, loading }) => {
    const handleLanguageSelect = (language: string) => {
        onNext({ language });
    };
    
    return (
        <div className="space-y-6">
            <div className="text-center">
                <Globe className="mx-auto h-12 w-12 text-emerald-600 mb-4" />
                <h2 className="text-2xl font-semibold mb-2">Select Your Language</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Choose your preferred language to continue
                </p>
            </div>
            
            <div className="grid gap-4 sm:grid-cols-3">
                {languages.map((lang) => (
                    <Card 
                        key={lang.code}
                        className="cursor-pointer p-6 text-center transition-all hover:border-emerald-600 hover:shadow-lg"
                        onClick={() => !loading && handleLanguageSelect(lang.code)}
                    >
                        <h3 className="text-lg font-medium mb-1">{lang.name}</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400">{lang.greeting}</p>
                        <ChevronRight className="mx-auto mt-4 h-5 w-5 text-gray-400" />
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default LanguageSelection;