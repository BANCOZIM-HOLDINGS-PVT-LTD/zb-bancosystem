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
                        href="tel:+263705074666"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <Phone className="h-3.5 w-3.5" />
                        <span>Cell number: 078 507 4666</span>
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
                        href="https://www.google.com/maps/place/Pockets+Building+Harare/@-17.8303098,31.0491945,17z/data=!3m1!4b1!4m6!3m5!1s0x1931a4e66426cc85:0x36dcd4425a2951a0!8m2!3d-17.8303149!4d31.0517694!16s%2Fg%2F11c57dj0pn?authuser=0&entry=ttu&g_ep=EgoyMDI2MDMwOS4wIKXMDSoASAFQAw%3D%3D"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1.5 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        <MapPin className="h-3.5 w-3.5" />
                        <span>5th Floor, Pockets Building, Corner J. Moyo & J. Nyerere Rd</span>
                    </a>

                </div>
            </div>
        </footer>
    );
};

export default Footer;
