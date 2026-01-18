import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CheckCircle, Info } from 'lucide-react';
import { WizardData } from '../ApplicationWizard';

interface HousePlanApprovalStepProps {
    data: WizardData;
    updateData: (data: Partial<WizardData>) => void;
    onNext: () => void;
    onBack: () => void;
}

const HousePlanApprovalStep: React.FC<HousePlanApprovalStepProps> = ({ data, updateData, onNext, onBack }) => {

    const handleAccept = () => {
        updateData({
            formResponses: {
                ...data.formResponses,
                planApproved: true
            }
        });
        onNext();
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in duration-500 pb-24 sm:pb-8">
            <div className="text-center mb-4 sm:mb-8">
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">House Plan Approval</h2>
                <p className="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                    Review and approve the standard design plan.
                </p>
            </div>

            <Card className="p-6 border-emerald-100 dark:border-emerald-900 bg-white dark:bg-gray-800 shadow-lg">
                <div className="aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-6 relative overflow-hidden group">
                    {/* Placeholder for Plan Image */}
                    <div className="text-center">
                        <div className="text-6xl mb-4">🏠</div>
                        <p className="text-gray-500 font-medium">Standard Core House Plan (3 Roomed)</p>
                        <p className="text-xs text-gray-400 mt-2">Plan Image Placeholder</p>
                    </div>
                </div>

                <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800 mb-6">
                    <div className="flex items-start gap-3">
                        <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <h4 className="font-semibold text-blue-800 dark:text-blue-300 mb-1">Standard Plan Note</h4>
                            <p className="text-sm text-blue-600 dark:text-blue-400 leading-relaxed">
                                All core house (3 roomed) plans are standard as per the plan displayed above.
                                By proceeding, you accept this standard design.
                            </p>
                        </div>
                    </div>
                </div>

            </Card>

            {/* Navigation Buttons */}
            <div className="flex justify-between gap-4 pt-4 mb-32">
                <Button variant="outline" onClick={onBack}>
                    Back
                </Button>
                <Button onClick={handleAccept} className="bg-emerald-600 hover:bg-emerald-700 text-white">
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Accept & Continue
                </Button>
            </div>
        </div>
    );
};

export default HousePlanApprovalStep;
