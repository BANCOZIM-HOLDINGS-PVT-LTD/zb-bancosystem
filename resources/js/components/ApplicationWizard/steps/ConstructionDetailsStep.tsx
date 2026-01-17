import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AlertCircle, MapPin, Droplets, Home } from 'lucide-react';
import { WizardData } from '../ApplicationWizard';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface ConstructionDetailsStepProps {
    data: WizardData;
    updateData: (data: Partial<WizardData>) => void;
    onNext: () => void;
    onBack: () => void;
}

const ConstructionDetailsStep: React.FC<ConstructionDetailsStepProps> = ({ data, updateData, onNext, onBack }) => {
    // Local state for validation
    const [setting, setSetting] = useState<string>(data.formResponses?.constructionSetting || '');
    const [province, setProvince] = useState<string>(data.formResponses?.constructionProvince || '');
    const [waterNearby, setWaterNearby] = useState<string>(data.formResponses?.waterSourceNearby || '');
    const [waterSource, setWaterSource] = useState<string>(data.formResponses?.waterSourceType || '');
    const [owner, setOwner] = useState<string>(data.formResponses?.standOwner || '');
    const [error, setError] = useState<string>('');

    // Update parent state when local state changes
    useEffect(() => {
        updateData({
            formResponses: {
                ...data.formResponses,
                constructionSetting: setting,
                constructionProvince: province,
                waterSourceNearby: waterNearby,
                waterSourceType: waterSource,
                standOwner: owner
            }
        });
    }, [setting, province, waterNearby, waterSource, owner]);

    const handleNext = () => {
        if (!setting) {
            setError('Please select if the construction is in an urban or rural setting.');
            return;
        }
        if (setting === 'Urban' && !province) {
            setError('Please select the province.');
            return;
        }
        if (!waterNearby) {
            setError('Please indicate if there is a water source nearby.');
            return;
        }
        if (waterNearby === 'Yes' && !waterSource) {
            setError('Please select the type of water source.');
            return;
        }
        if (!owner) {
            setError('Please select the legal owner of the stand.');
            return;
        }

        setError('');
        onNext();
    };

    const ownerOptions = [
        "Me",
        "Spouse",
        "Me & Spouse",
        "Child",
        "Family Rural Village",
        "Employer",
        "Mortgage/Bank",
        "Parents",
        "Company"
    ];

    return (
        <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in duration-500">
            <div className="text-center mb-6">
                <h2 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Construction Details</h2>
                <p className="text-gray-600 dark:text-gray-400">
                    Tell us about the site where the house will be built.
                </p>
            </div>

            <Card className="p-8 space-y-8 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm">

                {/* Setting & Province */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2 mb-2">
                        <MapPin className="h-5 w-5 text-emerald-600" />
                        <h3 className="font-semibold text-lg text-gray-900 dark:text-gray-100">Location</h3>
                    </div>

                    <div className="grid gap-6">
                        <div className="space-y-3">
                            <Label>Is the house being constructed in an urban or rural setting?</Label>
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="radio"
                                        id="urban"
                                        name="setting"
                                        value="Urban"
                                        checked={setting === 'Urban'}
                                        onChange={(e) => { setSetting(e.target.value); }}
                                        className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <Label htmlFor="urban" className="font-normal cursor-pointer">Urban (Harare / Bulawayo)</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="radio"
                                        id="rural"
                                        name="setting"
                                        value="Rural"
                                        checked={setting === 'Rural'}
                                        onChange={(e) => { setSetting(e.target.value); setProvince(''); }}
                                        className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <Label htmlFor="rural" className="font-normal cursor-pointer">Rural</Label>
                                </div>
                            </div>
                        </div>

                        {setting === 'Urban' && (
                            <div className="space-y-2 animate-in fade-in slide-in-from-top-2">
                                <Label htmlFor="province">Select Province</Label>
                                <Select value={province} onValueChange={setProvince}>
                                    <SelectTrigger id="province">
                                        <SelectValue placeholder="Select province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Harare">Harare</SelectItem>
                                        <SelectItem value="Bulawayo">Bulawayo</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>

                    <Alert className="bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800">
                        <AlertCircle className="h-4 w-4 text-amber-600" />
                        <AlertDescription className="text-amber-800 dark:text-amber-300 text-xs">
                            <strong>Disclaimer:</strong> This information is important for planning purposes, if you give incorrect information it may attract extra costs.
                        </AlertDescription>
                    </Alert>
                </div>

                <div className="border-t border-gray-100 dark:border-gray-700"></div>

                {/* Water Source */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Droplets className="h-5 w-5 text-blue-600" />
                        <h3 className="font-semibold text-lg text-gray-900 dark:text-gray-100">Water Supply</h3>
                    </div>

                    <div className="space-y-4">
                        <div className="space-y-3">
                            <Label>Do you have a water source nearby?</Label>
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="radio"
                                        id="water-yes"
                                        name="waterNearby"
                                        value="Yes"
                                        checked={waterNearby === 'Yes'}
                                        onChange={(e) => { setWaterNearby(e.target.value); }}
                                        className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <Label htmlFor="water-yes" className="font-normal cursor-pointer">Yes</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <input
                                        type="radio"
                                        id="water-no"
                                        name="waterNearby"
                                        value="No"
                                        checked={waterNearby === 'No'}
                                        onChange={(e) => { setWaterNearby(e.target.value); setWaterSource(''); }}
                                        className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <Label htmlFor="water-no" className="font-normal cursor-pointer">No</Label>
                                </div>
                            </div>
                        </div>

                        {waterNearby === 'Yes' && (
                            <div className="space-y-3 animate-in fade-in slide-in-from-top-2">
                                <Label>What is the source?</Label>
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            id="src-well"
                                            name="waterSource"
                                            value="well_borehole"
                                            checked={waterSource === 'well_borehole'}
                                            onChange={(e) => setWaterSource(e.target.value)}
                                            className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                        />
                                        <Label htmlFor="src-well" className="font-normal cursor-pointer">On well / borehole</Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            id="src-river"
                                            name="waterSource"
                                            value="stream_river_dam"
                                            checked={waterSource === 'stream_river_dam'}
                                            onChange={(e) => setWaterSource(e.target.value)}
                                            className="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-600 cursor-pointer"
                                        />
                                        <Label htmlFor="src-river" className="font-normal cursor-pointer">Stream / river / dam within a 5km radius</Label>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="border-t border-gray-100 dark:border-gray-700"></div>

                {/* Ownership */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Home className="h-5 w-5 text-purple-600" />
                        <h3 className="font-semibold text-lg text-gray-900 dark:text-gray-100">Property Ownership</h3>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="owner">Who is the legal owner of the stand?</Label>
                        <Select value={owner} onValueChange={setOwner}>
                            <SelectTrigger id="owner">
                                <SelectValue placeholder="Select legal owner" />
                            </SelectTrigger>
                            <SelectContent>
                                {ownerOptions.map((opt) => (
                                    <SelectItem key={opt} value={opt}>{opt}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg text-xs text-gray-500 italic mt-4">
                        NB: The houses are to be built on the understanding that building plan approval will be done by the customer on a post construction regularization basis.
                    </div>
                </div>

                {error && (
                    <p className="text-sm text-red-600 animate-pulse font-medium text-center">
                        {error}
                    </p>
                )}

                <div className="flex justify-between pt-4">
                    <Button variant="outline" onClick={onBack}>
                        Back
                    </Button>
                    <Button onClick={handleNext} className="bg-emerald-600 hover:bg-emerald-700 text-white min-w-[120px]">
                        Continue
                    </Button>
                </div>
            </Card>
        </div>
    );
};

export default ConstructionDetailsStep;
