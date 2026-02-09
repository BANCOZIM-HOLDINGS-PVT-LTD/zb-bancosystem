import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertCircle, RotateCcw, Trash2 } from 'lucide-react';

interface SessionRecoveryModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sessionId: string;
    lastActivity: string;
    currentStep: string;
    onDiscard: () => Promise<void>;
    onContinue: () => void;
}

export function SessionRecoveryModal({
    open,
    onOpenChange,
    sessionId,
    lastActivity,
    currentStep,
    onDiscard,
    onContinue
}: SessionRecoveryModalProps) {
    const [isDiscarding, setIsDiscarding] = useState(false);

    const handleDiscard = async () => {
        setIsDiscarding(true);
        try {
            await onDiscard();
            onOpenChange(false);
        } catch (error) {
            console.error('Failed to discard session', error);
        } finally {
            setIsDiscarding(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-amber-600">
                        <AlertCircle className="h-5 w-5" />
                        Incomplete Application Found
                    </DialogTitle>
                    <DialogDescription>
                        We found an incomplete application associated with this phone number from {new Date(lastActivity).toLocaleDateString()}.
                    </DialogDescription>
                </DialogHeader>

                <div className="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-md my-4">
                    <div className="text-sm text-amber-900 dark:text-amber-100">
                        <strong>Current Status:</strong> {currentStep ? currentStep.replace('_', ' ').toUpperCase() : 'IN PROGRESS'}
                    </div>
                    <div className="mt-2 text-xs text-amber-700 dark:text-amber-300">
                        Would you like to continue where you left off, or discard it and start a new application?
                    </div>
                </div>

                <DialogFooter className="flex flex-col sm:flex-row gap-2">
                    <Button
                        variant="outline"
                        onClick={handleDiscard}
                        disabled={isDiscarding}
                        className="sm:w-1/2 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-900 dark:text-red-400"
                    >
                        {isDiscarding ? (
                            "Discarding..."
                        ) : (
                            <>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Start Fresh
                            </>
                        )}
                    </Button>
                    <Button
                        onClick={onContinue}
                        className="sm:w-1/2 bg-emerald-600 hover:bg-emerald-700"
                    >
                        <RotateCcw className="mr-2 h-4 w-4" />
                        Continue Application
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
