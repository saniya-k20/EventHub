<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require 'db_connection.php';

// Get all enquiries
try {
    $stmt = $pdo->query("SELECT * FROM enquiries ORDER BY created_at DESC");
    $enquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching enquiries: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Admin Header -->
        <header class="bg-[#8DA9C4] text-white p-4 shadow-md">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">EventHub Admin Panel</h1>
                <a href="admin_logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Logout
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto p-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-[#37526C]">Booking Enquiries</h2>
                
                <?php if (empty($enquiries)): ?>
                    <p class="text-gray-600">No enquiries found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr class="bg-gray-200 text-gray-700">
                                    <th class="py-3 px-4 text-left">ID</th>
                                    <th class="py-3 px-4 text-left">Name</th>
                                    <th class="py-3 px-4 text-left">Venue</th>
                                    <th class="py-3 px-4 text-left">Event Date</th>
                                    <th class="py-3 px-4 text-left">Guests</th>
                                    <th class="py-3 px-4 text-left">Status</th>
                                    <th class="py-3 px-4 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <?php foreach ($enquiries as $enquiry): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= htmlspecialchars($enquiry['id']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($enquiry['name']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($enquiry['venue']) ?></td>
                                    <td class="py-3 px-4"><?= date('d M Y', strtotime($enquiry['event_date'])) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($enquiry['guests']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?= $enquiry['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($enquiry['status'] === 'contacted' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= ucfirst($enquiry['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <button onclick="viewEnquiry(<?= $enquiry['id'] ?>)" 
                                                class="text-[#537CA2] hover:text-[#37526C] mr-2">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="updateStatus(<?= $enquiry['id'] ?>)" 
                                                class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Enquiry Detail Modal -->
    <div id="enquiryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-[#37526C]">Enquiry Details</h2>
                <button onclick="closeEnquiryModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="enquiryDetails" class="space-y-4">
                <!-- Details will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-[#37526C]">Update Status</h2>
                <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="statusForm" class="space-y-4">
                <input type="hidden" id="enquiryId">
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]">
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div>
                    <label for="adminNotes" class="block text-sm font-medium text-gray-700">Admin Notes</label>
                    <textarea id="adminNotes" name="adminNotes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-[#537CA2] focus:border-[#537CA2]"></textarea>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#537CA2]">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#537CA2] hover:bg-[#37526C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#537CA2]">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // View Enquiry Details
        function viewEnquiry(id) {
            fetch('get_enquiry.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const enquiry = data.enquiry;
                        const details = `
                            <div>
                                <h3 class="font-bold text-lg">${enquiry.name}</h3>
                                <p class="text-gray-600">${enquiry.email} | ${enquiry.phone}</p>
                            </div>
                            
                            <div>
                                <h4 class="font-bold">Venue:</h4>
                                <p>${enquiry.venue}</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 class="font-bold">Event Date:</h4>
                                    <p>${new Date(enquiry.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold">Number of Guests:</h4>
                                    <p>${enquiry.guests}</p>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="font-bold">Status:</h4>
                                <span class="px-2 py-1 rounded-full text-xs 
                                    ${enquiry.status === 'completed' ? 'bg-green-100 text-green-800' : 
                                      enquiry.status === 'contacted' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'}">
                                    ${enquiry.status.charAt(0).toUpperCase() + enquiry.status.slice(1)}
                                </span>
                            </div>
                            
                            ${enquiry.message ? `
                            <div>
                                <h4 class="font-bold">Additional Requirements:</h4>
                                <p>${enquiry.message}</p>
                            </div>
                            ` : ''}
                            
                            <div>
                                <h4 class="font-bold">Submitted On:</h4>
                                <p>${new Date(enquiry.created_at).toLocaleString()}</p>
                            </div>
                        `;
                        
                        document.getElementById('enquiryDetails').innerHTML = details;
                        document.getElementById('enquiryModal').classList.remove('hidden');
                    } else {
                        alert('Error loading enquiry details');
                    }
                });
        }

        function closeEnquiryModal() {
            document.getElementById('enquiryModal').classList.add('hidden');
        }

        // Update Status
        function updateStatus(id) {
            document.getElementById('enquiryId').value = id;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Handle status form submission
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('id', document.getElementById('enquiryId').value);
            
            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully');
                    location.reload();
                } else {
                    alert('Error updating status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        });

        // Close modals when clicking outside
        document.getElementById('enquiryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEnquiryModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>