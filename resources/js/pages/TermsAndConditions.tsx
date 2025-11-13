import React from 'react';
import { Head } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { ScrollText } from 'lucide-react';

export default function TermsAndConditions() {
    return (
        <>
            <Head title="Terms and Conditions" />
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl mx-auto">
                    <div className="text-center mb-8">
                        <ScrollText className="mx-auto h-16 w-16 text-emerald-600 mb-4" />
                        <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                            Terms and Conditions
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Last updated: {new Date().toLocaleDateString()}
                        </p>
                    </div>

                    <Card className="p-8 space-y-6">
                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                1. Introduction
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                Welcome to our loan application system. By accessing and using this service, you agree to be bound by these Terms and Conditions. Please read them carefully before proceeding with your application.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                2. Eligibility
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 mb-2">
                                To be eligible for a loan, you must:
                            </p>
                            <ul className="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                                <li>Be at least 18 years of age</li>
                                <li>Be a resident of Zimbabwe</li>
                                <li>Have a valid form of identification</li>
                                <li>Have a verifiable source of income</li>
                                <li>Provide accurate and truthful information</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                3. Application Process
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 mb-2">
                                By submitting a loan application, you:
                            </p>
                            <ul className="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                                <li>Authorize us to verify the information provided</li>
                                <li>Consent to credit checks and background verification</li>
                                <li>Understand that submission does not guarantee approval</li>
                                <li>Agree to provide additional documentation if requested</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                4. Loan Terms
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                All loan terms, including interest rates, repayment schedules, and fees, will be clearly stated in your loan agreement. You are required to review and accept the specific terms before the loan is disbursed.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                5. Repayment
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300 mb-2">
                                You agree to:
                            </p>
                            <ul className="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 ml-4">
                                <li>Repay the loan according to the agreed schedule</li>
                                <li>Pay all applicable fees and charges</li>
                                <li>Notify us immediately if you anticipate difficulty making payments</li>
                                <li>Accept that late payments may incur additional fees</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                6. Privacy and Data Protection
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                We are committed to protecting your personal information. All data collected during the application process will be handled in accordance with applicable data protection laws and our Privacy Policy. Your information will be used solely for the purpose of processing your loan application and will not be shared with third parties without your consent, except as required by law.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                7. Default and Consequences
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                Failure to repay your loan according to the agreed terms may result in additional fees, negative impact on your credit score, and legal action. We reserve the right to pursue all available remedies in the event of default.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                8. Changes to Terms
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                We reserve the right to modify these Terms and Conditions at any time. Any changes will be posted on this page with an updated revision date. Your continued use of our services after such changes constitutes acceptance of the new terms.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                9. Governing Law
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                These Terms and Conditions are governed by and construed in accordance with the laws of Zimbabwe. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Zimbabwe.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-white mb-3">
                                10. Contact Information
                            </h2>
                            <p className="text-gray-700 dark:text-gray-300">
                                If you have any questions about these Terms and Conditions, please contact us at:
                            </p>
                            <div className="mt-3 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <p className="text-gray-700 dark:text-gray-300">
                                    Email: support@bancosystem.com<br />
                                    Phone: +263 773 988 988<br />
                                    Address: 5th Floor Pockets Building 50 Jason Moyo Street ,P.O Box CY 2222, Harare
                                </p>
                            </div>
                        </section>

                        <div className="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p className="text-sm text-gray-600 dark:text-gray-400 text-center">
                                By clicking "Proceed to Form" on the application summary page, you acknowledge that you have read, understood, and agree to these Terms and Conditions.
                            </p>
                        </div>
                    </Card>

                    <div className="mt-8 text-center">
                        <button
                            onClick={() => window.close()}
                            className="text-emerald-600 hover:text-emerald-700 dark:text-emerald-500 dark:hover:text-emerald-400 font-medium"
                        >
                            Close this window
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
