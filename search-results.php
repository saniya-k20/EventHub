<?php
// Start PHP session and set headers at the very top
session_start();
header('Content-Type: text/html; charset=utf-8');

// Database connection
require __DIR__ . '/db_connection.php';

// Get search parameters
$location = $_GET['location'] ?? null;
$venue_type = $_GET['venue_type'] ?? null;
$price_range = $_GET['price_range'] ?? null;
$capacity = $_GET['capacity'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Search Results - Wedding Venues</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F9FAFB;
            color: #1F2937;
        }
        .venue-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: transform 0.3s ease;
        }
        .venue-image:hover {
            transform: scale(1.02);
        }
        #sideMenu {
            position: fixed;
            top: 80px;
            right: -250px;
            width: 250px;
            height: calc(100vh - 80px);
            background-color: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease-in-out;
            z-index: 1000;
            overflow-y: auto;
        }
        #sideMenu.show {
            right: 0;
        }
        .filter-pill {
            background-color: #F1F4F9;
            color: #37526C;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .filter-pill button {
            margin-left: 6px;
            color: #666;
        }
        .filter-pill button:hover {
            color: #333;
        }
        #bookingModal {
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }
        #bookingModal.show {
            opacity: 1;
            visibility: visible;
        }
                /* Search Modal Styles */
                .search-modal {
            display: none;
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 95%;
            max-width: 1200px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            padding: 20px;
        }
        
        .search-modal.show {
            display: block;
        }
        
        .search-modal-overlay {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .search-modal-overlay.show {
            display: block;
        }
        
        /* Search Options */
        .search-sections {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .search-section {
            padding: 15px;
            min-width: 0;
        }
        
        .search-section h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #537CA2;
            border-bottom: 2px solid #F1F4F9;
            padding-bottom: 8px;
        }
        
        .search-option {
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.2s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .search-option:hover {
            background-color: #F1F4F9;
        }
        
        .close-search-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .venue-type-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-[#8DA9C4] text-white p-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <img alt="EventHub Logo" class="h-10" src="eventhublogo.png"/>
            <span>Your Event Planning Platform</span>
        </div>
        
        <div class="flex-grow mx-4">
            <div class="flex justify-center">
                <input class="p-3 border border-gray-300 rounded-l-lg shadow-md focus:outline-none focus:ring-2 focus:ring-[#638475] transition duration-200 text-gray-800 placeholder-gray-400 w-96" 
                       placeholder="Search for Venues, Halls..." 
                       type="text"
                       onclick="openSearchModal()"
                       readonly />
                <button class="bg-[#537CA2] p-3 rounded-r-lg shadow-md hover:bg-[#37526C] transition duration-200" onclick="openSearchModal()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="flex items-center space-x-4">
            <select class="bg-white text-black p-2 rounded" onchange="handleCitySelection(this)">
                <option value="">Popular Search Locations</option>
                <option value="mumbai.html">Mumbai</option>
                <option value="delhi.html">Delhi</option>
                <option value="bangalore.html">Bangalore</option>
            </select>
            <button class="bg-[#537CA2] p-2 rounded-lg shadow-md hover:bg-[#37526C] transition duration-200" onclick="window.location.href='login.html'">Log In</button>
            <div class="relative">
                <button class="bg-[#537CA2] p-2 rounded-lg shadow-md hover:bg-[#37526C] transition duration-200" onclick="toggleSideMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Search Modal -->
<!-- Updated Search Modal -->
<div id="searchModalOverlay" class="search-modal-overlay"></div>
<div id="searchModal" class="search-modal">
    <button class="close-search-modal" onclick="closeSearchModal()">&times;</button>
    <h2 class="text-2xl font-bold mb-6 text-[#537CA2]">Advanced Search</h2>
    
    <div class="search-sections">
        <div class="search-section">
            <h3>Popular Locations</h3>
            <div class="search-option" onclick="selectSearchOption('Mumbai')">Mumbai</div>
            <div class="search-option" onclick="selectSearchOption('Delhi')">Delhi</div>
            <div class="search-option" onclick="selectSearchOption('Bangalore')">Bangalore</div>
            <div class="search-option" onclick="selectSearchOption('Hyderabad')">Hyderabad</div>
            <div class="search-option" onclick="selectSearchOption('Chennai')">Chennai</div>
        </div>
        
        <div class="search-section">
            <h3>Price Range (per plate)</h3>
            <div class="search-option" onclick="selectPriceRange('0-1000')">Under ₹1,000</div>
            <div class="search-option" onclick="selectPriceRange('1000-2000')">₹1,000 - ₹2,000</div>
            <div class="search-option" onclick="selectPriceRange('2000-3000')">₹2,000 - ₹3,000</div>
            <div class="search-option" onclick="selectPriceRange('3000-5000')">₹3,000 - ₹5,000</div>
            <div class="search-option" onclick="selectPriceRange('5000+')">Above ₹5,000</div>
        </div>
        
        <div class="search-section">
            <h3>Venue Type</h3>
            <div class="venue-type-options">
                <div class="search-option" onclick="selectVenueType('Banquet Hall')">Banquet Hall</div>
                <div class="search-option" onclick="selectVenueType('Hotel')">Hotel</div>
                <div class="search-option" onclick="selectVenueType('Garden')">Garden</div>
                <div class="search-option" onclick="selectVenueType('Beach')">Beach</div>
                <div class="search-option" onclick="selectVenueType('Resort')">Resort</div>
                <div class="search-option" onclick="selectVenueType('5 Star Hotel')">5 Star Hotel</div>
                <div class="search-option" onclick="selectVenueType('Iconic 5 Star Hotel')">Iconic 5 Star Hotel</div>
                <div class="search-option" onclick="selectVenueType('Luxury 5 Star Hotel')">Luxury 5 Star Hotel</div>
            </div>
        </div>
        
        <div class="search-section">
            <h3>Capacity</h3>
            <div class="search-option" onclick="selectCapacity('0-100')">Up to 100 guests</div>
            <div class="search-option" onclick="selectCapacity('100-300')">100 - 300 guests</div>
            <div class="search-option" onclick="selectCapacity('300-500')">300 - 500 guests</div>
            <div class="search-option" onclick="selectCapacity('500-1000')">500 - 1,000 guests</div>
            <div class="search-option" onclick="selectCapacity('1000+')">1,000+ guests</div>
        </div>
    </div>
    
    <div class="mt-6 flex justify-end">
        <button onclick="performSearch()" class="bg-[#537CA2] text-white px-6 py-2 rounded hover:bg-[#37526C]">Search</button>
    </div>
</div>

<script>

// Search Modal Functions
function openSearchModal() {
            document.getElementById('searchModal').classList.add('show');
            document.getElementById('searchModalOverlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSearchModal() {
            document.getElementById('searchModal').classList.remove('show');
            document.getElementById('searchModalOverlay').classList.remove('show');
            document.body.style.overflow = '';
        }

        document.getElementById('searchModalOverlay').addEventListener('click', closeSearchModal);

        // Search option selection functions
        let selectedOptions = {
            location: null,
            priceRange: null,
            venueType: null,
            capacity: null
        };

        function selectSearchOption(location) {
            document.querySelectorAll('.search-section:nth-child(1) .search-option').forEach(option => {
                option.style.backgroundColor = 'transparent';
                option.style.fontWeight = '400';
            });
            event.target.style.backgroundColor = '#E2E8F0';
            event.target.style.fontWeight = '600';
            selectedOptions.location = location;
        }

        function selectPriceRange(range) {
            document.querySelectorAll('.search-section:nth-child(2) .search-option').forEach(option => {
                option.style.backgroundColor = 'transparent';
                option.style.fontWeight = '400';
            });
            event.target.style.backgroundColor = '#E2E8F0';
            event.target.style.fontWeight = '600';
            selectedOptions.priceRange = range;
        }

        function selectVenueType(type) {
            document.querySelectorAll('.search-section:nth-child(3) .search-option').forEach(option => {
                option.style.backgroundColor = 'transparent';
                option.style.fontWeight = '400';
            });
            event.target.style.backgroundColor = '#E2E8F0';
            event.target.style.fontWeight = '600';
            selectedOptions.venueType = type;
        }

        function selectCapacity(capacity) {
            document.querySelectorAll('.search-section:nth-child(4) .search-option').forEach(option => {
                option.style.backgroundColor = 'transparent';
                option.style.fontWeight = '400';
            });
            event.target.style.backgroundColor = '#E2E8F0';
            event.target.style.fontWeight = '600';
            selectedOptions.capacity = capacity;
        }

        function performSearch() {
            const searchParams = new URLSearchParams();
            
            if (selectedOptions.location) {
                searchParams.append('location', selectedOptions.location.toLowerCase());
            }
            if (selectedOptions.priceRange) {
                searchParams.append('price_range', selectedOptions.priceRange.replace('+', '%2B'));
            }
            if (selectedOptions.venueType) {
                searchParams.append('venue_type', selectedOptions.venueType.toLowerCase().replace(/\s+/g, '_'));
            }
            if (selectedOptions.capacity) {
                searchParams.append('capacity', selectedOptions.capacity.replace('+', '%2B'));
            }

            closeSearchModal();
            window.location.href = `search-results.php?${searchParams.toString()}`;
        }

        // Handle city selection
        function handleCitySelection(select) {
            const value = select.value;
            if (value) {
                window.location.href = value;
            }
        }

</script>
    
    <!-- Side Menu -->
    <div id="sideMenu">
        <div class="p-4">
            <button class="text-gray-600 hover:text-gray-800" onclick="toggleSideMenu()">
                <i class="fas fa-times"></i>
            </button>
            <ul class="mt-4">
                <li><a href="home.html" class="block py-2 text-gray-700 hover:bg-gray-100">Home</a></li>
                <li><a href="#" class="block py-2 text-gray-700 hover:bg-gray-100">Profile</a></li>
                <li><a href="#" class="block py-2 text-gray-700 hover:bg-gray-100">Settings</a></li>
                <li><a href="logout.php" class="block py-2 text-gray-700 hover:bg-gray-100">Log Out</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <main class="p-4">
        <div class="text-sm text-gray-500 mb-4">
            <a href="home.html" class="text-[#537CA2] hover:underline">Home</a> &gt; 
            <span>Search Results</span>
        </div>
        
        <h1 class="text-2xl font-bold mb-2">Search Results</h1>
        
        <!-- Search Filters Summary -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-6">
            <div class="flex flex-wrap items-center">
                <span class="text-sm text-gray-600 mr-2">Filters:</span>
                <?php if ($location): ?>
                    <span class="filter-pill">
                        Location: <?php echo htmlspecialchars(ucfirst($location)); ?>
                        <button onclick="removeFilter('location')">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
                
                <?php if ($venue_type): ?>
                    <span class="filter-pill">
                        Type: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $venue_type))); ?>
                        <button onclick="removeFilter('venue_type')">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
                
                <?php if ($price_range): ?>
                    <span class="filter-pill">
                        Price: <?php 
                            if ($price_range === '5000%2B') {
                                echo 'Above ₹5,000';
                            } else {
                                $range = str_replace('%2B', '+', $price_range);
                                echo '₹' . str_replace('-', ' - ₹', $range);
                            }
                        ?>
                        <button onclick="removeFilter('price_range')">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
                
                <?php if ($capacity): ?>
                    <span class="filter-pill">
                        Capacity: <?php 
                            if ($capacity === '1000%2B') {
                                echo '1,000+ guests';
                            } else {
                                $cap = str_replace('%2B', '+', $capacity);
                                echo str_replace('-', ' - ', $cap) . ' guests';
                            }
                        ?>
                        <button onclick="removeFilter('capacity')">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                <?php endif; ?>
                
                <?php if ($location || $venue_type || $price_range || $capacity): ?>
                    <button onclick="clearAllFilters()" class="text-sm text-[#537CA2] hover:text-[#37526C] ml-2">
                        Clear all
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Search Results Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="searchResults">
            <!-- Results will be loaded here via JavaScript -->
        </div>
        
        <!-- Loading and No Results messages -->
        <div id="loadingSpinner" class="flex justify-center items-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#537CA2]"></div>
        </div>
        
        <div id="noResults" class="hidden text-center py-8">
            <h3 class="text-xl font-medium text-gray-600">No venues found matching your criteria.</h3>
            <p class="text-gray-500 mt-2">Try adjusting your search filters.</p>
            <button onclick="window.location.href='home.html'" class="mt-4 bg-[#537CA2] hover:bg-[#37526C] text-white px-6 py-2 rounded-lg transition-colors">
                Back to Home
            </button>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#537CA2] text-white p-8 mt-8 w-full">
        <div class="mx-auto max-w-6xl">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:underline hover:text-[#37526C]">Home</a></li>
                        <li><a href="#" class="hover:underline hover:text-[#37526C]">Venues</a></li>
                        <li><a href="#" class="hover:underline hover:text-[#37526C]">Profile</a></li>
                        <li><a href="#" class="hover:underline hover:text-[#37526C]">About Us</a></li>
                        <li><a href="#" class="hover:underline hover:text-[#37526C]">Contact Us</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-white hover:text-[#37526C] text-xl"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover:text-[#37526C] text-xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover:text-[#37526C] text-xl"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white hover:text-[#37526C] text-xl"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="text-white hover:text-[#37526C] text-xl"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                    <p class="mb-2 hover:text-[#37526C]"><i class="fas fa-map-marker-alt mr-2"></i>123 Connaught Place, Mumbai, India</p>
                    <p class="mb-2 hover:text-[#37526C]"><i class="fas fa-phone-alt mr-2"></i>+91 98765 43210</p>
                    <p class="mb-2 hover:text-[#37526C]"><i class="fas fa-envelope mr-2"></i>info@eventhub.com</p>
                </div>
            </div>
            
            <div class="border-t border-[#37526C] mt-8 pt-4 text-center">
                <p>&copy; 2023 EventHub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Enquiry Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        // Enhanced search results loader
        async function loadSearchResults() {
            const urlParams = new URLSearchParams(window.location.search);
            const loadingSpinner = document.getElementById('loadingSpinner');
            const noResults = document.getElementById('noResults');
            const resultsContainer = document.getElementById('searchResults');
            
            try {
                // Show loading state
                loadingSpinner.classList.remove('hidden');
                noResults.classList.add('hidden');
                resultsContainer.innerHTML = '';
                
                // Check if there are any filters
                if (urlParams.toString().length === 0) {
                    noResults.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    return;
                }
                
                // Build API URL with proper encoding
                const apiUrl = `search-venues.php?${urlParams.toString()}`;
                
                const response = await fetch(apiUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (!data || !data.data || data.data.length === 0) {
                    noResults.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    return;
                }
                
                // Hide spinner and display results
                loadingSpinner.classList.add('hidden');
                
                data.data.forEach(venue => {
                    const venueCard = `
                        <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <img src="${venue.image_url || 'https://via.placeholder.com/600x400?text=Venue+Image'}" 
                                 alt="${venue.name}" 
                                 class="venue-image"
                                 onerror="this.src='https://via.placeholder.com/600x400?text=Venue+Image'">
                            <h2 class="text-xl font-bold text-[#37526C]">${venue.name}</h2>
                            <div class="flex items-center text-sm text-gray-500 my-2">
                                <i class="fas fa-map-marker-alt mr-2 text-[#537CA2]"></i>
                                ${venue.location}
                            </div>
                            <div class="flex items-center text-sm text-gray-500 mb-2">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                ${venue.rating || '4.0'} (${venue.review_count || '0'} reviews)
                            </div>
                            <div class="text-sm text-gray-600 mb-2">
                                <span class="font-medium">Type:</span> ${venue.venue_type || 'Venue'}
                            </div>
                            <div class="text-sm text-gray-600 mb-2">
                                <span class="font-medium">Veg:</span> ₹${venue.veg_price || '0'} per plate<br>
                                ${venue.non_veg_price ? `<span class="font-medium">Non-Veg:</span> ₹${venue.non_veg_price} per plate` : ''}
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Capacity:</span> ${venue.min_capacity || '0'}-${venue.max_capacity || '0'} guests
                            </div>
                            <button onclick="openBookingModal('${venue.name.replace(/'/g, "\\'")}')" 
                                    class="mt-4 w-full bg-[#537CA2] hover:bg-[#37526C] text-white py-2 rounded-lg transition-colors">
                                View Details
                            </button>
                        </div>
                    `;
                    resultsContainer.insertAdjacentHTML('beforeend', venueCard);
                });
                
            } catch (error) {
                console.error('Search error:', error);
                loadingSpinner.classList.add('hidden');
                noResults.innerHTML = `
                    <h3 class="text-xl font-medium text-gray-600">Error loading results</h3>
                    <p class="text-gray-500 mt-2">${error.message}</p>
                    <button onclick="window.location.href='home.html'" class="mt-4 bg-[#537CA2] hover:bg-[#37526C] text-white px-6 py-2 rounded-lg transition-colors">
                        Back to Home
                    </button>
                `;
                noResults.classList.remove('hidden');
            }
        }
        
        // Filter functions
        function removeFilter(filterName) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(filterName);
            window.location.search = urlParams.toString();
        }
        
        function clearAllFilters() {
            window.location.search = '';
        }
        
        // Modal functions
        function openBookingModal(venueName) {
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.style.overflow = 'hidden';
            
            // Reset modal content
            resetModalContent();
            
            // Set venue name in the form
            const venueInput = document.getElementById('venue');
            if (venueInput) {
                venueInput.value = venueName;
            }
        }

        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        function resetModalContent() {
            document.getElementById('modalContent').innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-[#37526C]">Booking Enquiry</h2>
                    <button onclick="closeBookingModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <p class="mb-4 text-gray-600">For booking enquiry, please fill out the form below and we'll contact you shortly.</p>
                
                <form id="bookingForm" method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name*</label>
                            <input type="text" id="name" name="name" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email*</label>
                            <input type="email" id="email" name="email" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number*</label>
                            <input type="tel" id="phone" name="phone" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                        </div>
                        
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700">Event Date*</label>
                            <input type="date" id="date" name="date" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                        </div>
                    </div>
                    
                    <div>
                        <label for="venue" class="block text-sm font-medium text-gray-700">Venue Name*</label>
                        <input type="text" id="venue" name="venue" readonly 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100">
                    </div>
                    
                    <div>
                        <label for="guests" class="block text-sm font-medium text-gray-700">Approximate Number of Guests*</label>
                        <select id="guests" name="guests" required 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                            <option value="">Select number of guests</option>
                            <option value="50-100">50-100</option>
                            <option value="100-300">100-300</option>
                            <option value="300-500">300-500</option>
                            <option value="500+">500+</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Additional Requirements</label>
                        <textarea id="message" name="message" rows="3" 
                                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="closeBookingModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#537CA2]">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#537CA2] hover:bg-[#37526C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#537CA2]">
                            Submit Enquiry
                        </button>
                    </div>
                </form>
            `;
            
            // Re-attach event listeners
            document.getElementById('bookingForm').addEventListener('submit', handleFormSubmit);
        }

        function showSuccessMessage() {
            document.getElementById('modalContent').innerHTML = `
                <div class="p-6 text-center">
                    <div class="text-green-500 text-5xl mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-[#37526C] mb-2">Enquiry Submitted Successfully!</h2>
                    <p class="text-gray-600 mb-6">Details Submitted! Our Team will Contact you shortly</p>
                    <button onclick="closeBookingModal()" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#537CA2] hover:bg-[#37526C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#537CA2]">
                        Close
                    </button>
                </div>
            `;
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            fetch('submit_enquiry.php', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessMessage();
                } else {
                    throw new Error(data.message || 'Submission failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting enquiry: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        // Side menu functions
        function toggleSideMenu() {
            document.getElementById("sideMenu").classList.toggle("show");
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const sideMenu = document.getElementById("sideMenu");
            const menuButton = document.querySelector('[onclick="toggleSideMenu()"]');
            
            if (!sideMenu.contains(event.target) && event.target !== menuButton && !menuButton.contains(event.target)) {
                sideMenu.classList.remove("show");
            }
        });

        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });

        // Load results when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadSearchResults();
            document.getElementById('bookingForm')?.addEventListener('submit', handleFormSubmit);
        });
    </script>
</body>
</html>