import React from 'react';
import { Mail, Phone, Globe } from 'lucide-react';

const Footer: React.FC = () => {
    return (
        <footer className="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg">
            <div className="max-w-7xl mx-auto px-4 py-2">
                <div className="flex flex-wrap items-center justify-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <a
                        href="https://microbizimbabwe.co.zw"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Globe className="h-3.5 w-3.5" />
                        <span>microbizimbabwe.co.zw</span>
                    </a>

                    <span className="hidden sm:inline text-gray-300 dark:text-gray-700">|</span>

                    <a
                        href="https://wa.me/263773988988"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Phone className="h-3.5 w-3.5" />
                        <span>WhatsApp: 0773 988 988</span>
                    </a>

                    <span className="hidden sm:inline text-gray-300 dark:text-gray-700">|</span>

                    <a
                        href="mailto:support@bancozim.com"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Mail className="h-3.5 w-3.5" />
                        <span>support@bancozim.com</span>
                    </a>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
