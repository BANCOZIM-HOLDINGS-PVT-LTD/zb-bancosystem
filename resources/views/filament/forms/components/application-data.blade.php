<div class="space-y-6">
    @php
        $formData = $getState();
        $formResponses = $formData['formResponses'] ?? [];
        $selectedBusiness = $formData['selectedBusiness'] ?? [];
        $documents = $formData['documents'] ?? [];
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">Personal Information</h3>
            
            <div class="space-y-3">
                @if(!empty($formResponses['firstName']) || !empty($formResponses['lastName']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['firstName'] ?? '' }} {{ $formResponses['lastName'] ?? '' }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['idNumber']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Number</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['idNumber'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['dateOfBirth']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Date of Birth</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['dateOfBirth'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['gender']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Gender</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($formResponses['gender']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['maritalStatus']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Marital Status</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($formResponses['maritalStatus']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['email']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['email'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['phoneNumber']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['phoneNumber'] }}</span>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">Address Information</h3>
            
            <div class="space-y-3">
                @if(!empty($formResponses['address']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Street Address</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['address'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['city']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">City</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['city'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['province']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Province</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['province'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['postalCode']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Postal Code</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['postalCode'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['residentialStatus']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Residential Status</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($formResponses['residentialStatus']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['yearsAtAddress']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Years at Address</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['yearsAtAddress'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">Employment Information</h3>
            
            <div class="space-y-3">
                @if(!empty($formData['employer']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Employer</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formData['employer'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['employmentStatus']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Employment Status</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($formResponses['employmentStatus']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['jobTitle']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Title</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['jobTitle'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['monthlyIncome']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Income</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">${{ number_format($formResponses['monthlyIncome'], 2) }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['employmentLength']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Years Employed</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['employmentLength'] }}</span>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">Loan Information</h3>
            
            <div class="space-y-3">
                @if(!empty($selectedBusiness['name']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Business Type</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $selectedBusiness['name'] }}</span>
                    </div>
                @endif
                
                @if(!empty($formData['finalPrice']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Amount</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">${{ number_format($formData['finalPrice'], 2) }}</span>
                    </div>
                @endif
                
                @if(!empty($formData['creditTerm']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Term</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formData['creditTerm'] }} months</span>
                    </div>
                @endif
                
                @if(!empty($formData['monthlyPayment']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Payment</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">${{ number_format($formData['monthlyPayment'], 2) }}</span>
                    </div>
                @endif
                
                @if(!empty($formData['hasAccount']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Existing Account</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formData['hasAccount'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif
                
                @if(!empty($formResponses['loanPurpose']))
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Purpose</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $formResponses['loanPurpose'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @if(!empty($documents))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-4">Documents</h3>
            
            <div class="space-y-3">
                @if(!empty($documents['uploadedDocuments']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($documents['uploadedDocuments'] as $docType => $docList)
                            @foreach($docList as $index => $document)
                                @php
                                    // Handle both string paths and array structures
                                    $documentPath = is_array($document) ? ($document['path'] ?? $document['url'] ?? '') : $document;
                                @endphp
                                @if(!empty($documentPath))
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ ucwords(str_replace('_', ' ', $docType)) }} {{ count($docList) > 1 ? ($index + 1) : '' }}
                                            </span>
                                            <a href="{{ Storage::disk('public')->url($documentPath) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                @endif
                
                @if(!empty($documents['selfie']) || !empty($documents['signature']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        @if(!empty($documents['selfie']))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Applicant Photo</span>
                                    <a href="{{ Storage::disk('public')->url($documents['selfie']) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        View
                                    </a>
                                </div>
                            </div>
                        @endif
                        
                        @if(!empty($documents['signature']))
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Applicant Signature</span>
                                    <a href="{{ Storage::disk('public')->url($documents['signature']) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        View
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>