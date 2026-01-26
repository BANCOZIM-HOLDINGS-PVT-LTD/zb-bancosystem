import React from 'react';
import { Mail, Phone, Globe, MapPin } from 'lucide-react';

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
                        <span>microbizimbabwe.com</span>
                    </a>

                    <span className="hidden sm:inline text-gray-300 dark:text-gray-700">|</span>

                    <a
                        href="tel:086 44 988 988"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Phone className="h-3.5 w-3.5" />
                        <span>Customer Support: 086 44 988988</span>
                    </a>
                    <span className="hidden sm:inline text-gray-300 dark:text-gray-700">|</span>

                    <a
                        href="mailto:sales@microbizimbabwe.com"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Mail className="h-3.5 w-3.5" />
                        <span>sales@microbizimbabwe.com</span>
                    </a>
                    <span className="hidden sm:inline text-gray-300 dark:text-gray-700">|</span>
                    <a
                        href="https://maps.google.com/?q=Joina+City,+Harare"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <MapPin className="h-3.5 w-3.5" />
                        <span>12th Floor, Joina City Building Corner J. Moyo & J. Nyerere Rd</span>
                    </a>

                </div>
            </div>
        </footer>
    );
};

export default Footer;
