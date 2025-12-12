import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react'
import { Check, ChevronDown } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

interface CountryCode {
    name: string
    dial_code: string
    code: string
}

interface CountryCodeSelectProps {
    value: string
    onChange: (value: string) => void
    countries: CountryCode[]
    disabled?: boolean
}

export default function CountryCodeSelect({ value, onChange, countries, disabled }: CountryCodeSelectProps) {
    const [query, setQuery] = useState('')

    const selectedCountry = countries.find(c => c.dial_code === value) || countries.find(c => c.dial_code === '+263')

    const filteredCountries =
        query === ''
            ? countries
            : countries.filter((country) => {
                return (
                    country.name.toLowerCase().includes(query.toLowerCase()) ||
                    country.dial_code.includes(query) ||
                    country.code.toLowerCase().includes(query.toLowerCase())
                )
            })

    return (
        <div className="w-[120px] shrink-0">
            <Combobox
                value={value}
                onChange={(code: string) => onChange(code)}
                disabled={disabled}
            >
                <div className="relative">
                    <div className="relative w-full cursor-default overflow-hidden rounded-md border border-input bg-background text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:text-sm">
                        <ComboboxInput
                            className="w-full border-none py-2 pl-3 pr-8 text-sm leading-5 text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100 bg-transparent"
                            displayValue={(code: string | null) => {
                                if (!code) return '+263'; // Fallback
                                // We want to display the code, but we passed the dial_code as value.
                                // But wait, the input is for searching.
                                // If we want to show the selected value when not searching, we might need a button approach or handle displayValue carefully.
                                // Actually, standard ComboboxInput shows the text.
                                // Let's just show the dial_code if not editing, or allow typing to search.
                                return code;
                            }}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="+263"
                        />
                        <ComboboxButton className="absolute inset-y-0 right-0 flex items-center pr-2">
                            <ChevronDown
                                className="h-4 w-4 text-gray-400"
                                aria-hidden="true"
                            />
                        </ComboboxButton>
                    </div>
                    <ComboboxOptions className="absolute mt-1 max-h-60 w-[300px] overflow-auto rounded-md bg-popover py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm z-50">
                        {filteredCountries.length === 0 && query !== '' ? (
                            <div className="relative cursor-default select-none py-2 px-4 text-gray-700 dark:text-gray-300">
                                Nothing found.
                            </div>
                        ) : (
                            filteredCountries.map((country) => (
                                <ComboboxOption
                                    key={`${country.code}-${country.dial_code}`}
                                    className={({ focus }) =>
                                        `relative cursor-default select-none py-2 pl-10 pr-4 ${focus ? 'bg-accent text-accent-foreground' : 'text-popover-foreground'
                                        }`
                                    }
                                    value={country.dial_code}
                                >
                                    {({ selected, focus }) => (
                                        <>
                                            <span
                                                className={`block truncated ${selected ? 'font-medium' : 'font-normal'
                                                    }`}
                                            >
                                                {country.code} ({country.dial_code}) - {country.name}
                                            </span>
                                            {selected ? (
                                                <span
                                                    className={`absolute inset-y-0 left-0 flex items-center pl-3 ${focus ? 'text-accent-foreground' : 'text-primary'
                                                        }`}
                                                >
                                                    <Check className="h-4 w-4" aria-hidden="true" />
                                                </span>
                                            ) : null}
                                        </>
                                    )}
                                </ComboboxOption>
                            ))
                        )}
                    </ComboboxOptions>
                </div>
            </Combobox>
        </div>
    )
}
