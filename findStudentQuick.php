<?php 
require_once "authCheckPIO.php";
restrictToAdmin();
// include "includes/connect_db_euclid_pio.php" ; 
include "connectDatabase.php";
include "includes/date_data.php" ;
$date = date('d-M-Y') ;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Data Table with Filtering & Sorting</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }
        .table-header {
            cursor: pointer;
            transition: background-color 0.2s;
            user-select: none;
            position: sticky;
            top: 0;
            background-color: #3b82f6; /* Blue-600 */
        }
        .table-header:hover {
            background-color: #2563eb; /* Blue-700 */
        }
        .table-row:nth-child(even) {
            background-color: #f8fafc; /* Light grey for zebra striping */
        }
        .loading-spinner {
            border-top-color: #3498db;
            -webkit-animation: spin 1s linear infinite;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Style for small arrow indicator */
        .sort-arrow {
            display: inline-block;
            margin-left: 0.5rem;
            width: 0;
            height: 0;
            border-style: solid;
        }
        .asc .sort-arrow {
            border-width: 0 4px 6px 4px;
            border-color: transparent transparent #fff transparent;
        }
        .desc .sort-arrow {
            border-width: 6px 4px 0 4px;
            border-color: #fff transparent transparent transparent;
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto bg-white p-6 md:p-10 rounded-xl shadow-2xl">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-2">
            Find students
        </h1>

        <!-- Controls: Search, Button, and Status -->
        <div class="mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center">
            <input type="text" id="filterInput" placeholder="Filter: term1 & term2, term3 | -exclude"
                   class="w-full md:w-1/3 p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                   oninput="handleFilter(this.value)">
            
            <button id="copyButton" onclick="copyFilteredRecords()"
                    class="px-4 py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-150 whitespace-nowrap">
                Copy Filtered Records (CSV)
            </button>

            <!-- Status and Feedback Container -->
            <div class="flex flex-col md:flex-row md:items-center md:ml-auto gap-2">
                <span id="recordCount" class="text-sm text-gray-600 font-medium"></span>
                <span id="copyFeedback" class="text-sm font-semibold transition duration-300 opacity-0"></span>
                 <button class="btn btn-warning shadow-sm px-6 font-bold" id="back" onclick="history.back()">
            GO BACK
        </button>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead id="tableHead" class="bg-blue-600 text-white sticky top-0">
                    <!-- Header will be dynamically generated here -->
                </thead>
                <tbody id="tableBody" class="bg-white divide-y divide-gray-100">
                    <!-- Data will be inserted here -->
                </tbody>
            </table>
        </div>

        <!-- Initial Loader/Feedback -->
        <div id="statusMessage" class="text-center p-10">
            <div class="flex items-center justify-center space-x-2">
                <div class="loading-spinner w-6 h-6 border-4 border-gray-200 rounded-full"></div>
                <span class="text-lg text-gray-600">Loading data...</span>
            </div>
        </div>
        
    </div>

    <script>
        // --- Application State ---
        let originalData = [];
        let filteredData = [];
        let sortConfig = { key: null, direction: 'asc' };
        
        // --- NOTE: Mock Data Source is removed ---
        
        /**
         * Fetches JSON data from the specified API endpoint.
         */
        async function fetchData() {
            // Show loading message while fetching
            document.getElementById('statusMessage').innerHTML = `
                <div class="flex items-center justify-center space-x-2 p-10">
                    <div class="loading-spinner w-6 h-6 border-4 border-gray-200 rounded-full"></div>
                    <span class="text-lg text-gray-600">Loading data...</span>
                </div>
            `;
            
            try {
                // --- ACTUAL FETCH CALL TO YOUR ENDPOINT ---
                const response = await fetch('/newAJAX/quickFind.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} - Could not connect to API.`);
                }
                
                const json = await response.json();
                
                // Check if the returned JSON is an array of objects
                if (!Array.isArray(json) || json.length === 0 || typeof json[0] !== 'object') {
                    throw new Error("API returned invalid or empty data.");
                }

                originalData = json;
                filteredData = json;
                
                // Clear the status message and render the table
                document.getElementById('statusMessage').innerHTML = '';
                renderTable(filteredData);
                updateRecordCount(filteredData.length);

            } catch (error) {
                console.error("Error fetching data:", error);
                document.getElementById('statusMessage').innerHTML = `<p class="text-red-500 font-semibold p-10">Failed to load data: ${error.message}. Please check the console for details.</p>`;
            }
        }

        /**
         * Renders the table content and sets up sortable headers.
         * @param {Array<Object>} data - The array of objects to display.
         */
        function renderTable(data) {
            const tableBody = document.getElementById('tableBody');
            const tableHead = document.getElementById('tableHead');
            
            // 1. Generate Header (only on first render, and only if data has been loaded)
            if (originalData.length > 0 && tableHead.children.length === 0) {
                const keys = Object.keys(originalData[0]);
                const headerRow = document.createElement('tr');
                headerRow.className = 'table-row';
                
                keys.forEach(key => {
                    const th = document.createElement('th');
                    th.textContent = formatHeader(key);
                    th.className = 'table-header px-6 py-3 text-left text-xs font-medium uppercase tracking-wider whitespace-nowrap';
                    th.dataset.key = key;
                    th.addEventListener('click', () => handleSort(key));
                    headerRow.appendChild(th);
                });
                tableHead.appendChild(headerRow);
            }

            // 2. Generate Body
            if (data.length === 0) {
                // Ensure we get the correct column count from the original data structure
                const colSpanCount = Object.keys(originalData[0] || {}).length || 1; 
                tableBody.innerHTML = `<tr><td colspan="${colSpanCount}" class="text-center py-6 text-gray-500">No matching records found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = data.map(item => {
                const values = Object.values(item);
                const cells = values.map(value => {
                    const displayValue = formatValue(value);
                    return `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${displayValue}</td>`;
                }).join('');
                return `<tr class="table-row">${cells}</tr>`;
            }).join('');
            
            // 3. Update header arrows
            updateHeaderArrows();
        }

        /**
         * Formats column keys for display.
         * @param {string} key 
         */
        function formatHeader(key) {
            return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1').trim();
        }
        
        /**
         * Formats values (e.g., currency).
         * @param {any} value 
         */
        function formatValue(value) {
            if (typeof value === 'number' && (value >= 1000 || value <= -1000)) {
                // Simple currency/large number formatting
                return `$${value.toLocaleString()}`;
            }
            return value;
        }

        /**
         * Updates the small arrow indicator on the currently sorted column.
         */
        function updateHeaderArrows() {
            document.querySelectorAll('#tableHead th').forEach(th => {
                th.classList.remove('asc', 'desc');
                th.querySelector('.sort-arrow')?.remove();

                if (th.dataset.key === sortConfig.key) {
                    th.classList.add(sortConfig.direction);
                    const arrow = document.createElement('span');
                    arrow.className = `sort-arrow`;
                    th.appendChild(arrow);
                }
            });
        }
        
        /**
         * Handles sorting logic when a header is clicked.
         * @param {string} key - The column key to sort by.
         */
        function handleSort(key) {
            let direction = 'asc';
            
            // If the same key is clicked, reverse the direction
            if (sortConfig.key === key && sortConfig.direction === 'asc') {
                direction = 'desc';
            }
            
            sortConfig = { key, direction };

            const sortedData = [...filteredData].sort((a, b) => {
                const valA = a[key];
                const valB = b[key];

                const numA = parseFloat(valA);
                const numB = parseFloat(valB);

                let comparison = 0;

                // Check if both values are valid, finite numbers for numeric sort
                if (!isNaN(numA) && isFinite(numA) && !isNaN(numB) && isFinite(numB)) {
                    // Numeric comparison
                    if (numA > numB) comparison = 1;
                    else if (numA < numB) comparison = -1;
                } else {
                    // Fallback to robust string comparison
                    const strA = String(valA).toLowerCase();
                    const strB = String(valB).toLowerCase();
                    if (strA > strB) comparison = 1;
                    else if (strA < strB) comparison = -1;
                }

                // Apply direction multiplier
                return direction === 'asc' ? comparison : comparison * -1;
            });

            filteredData = sortedData;
            renderTable(filteredData);
        }

        /**
         * Handles filtering logic based on the input text, supporting AND/OR/NOT logic.
         * @param {string} filterText - The text to search for, including logical operators.
         */
        function handleFilter(filterText) {
            const originalQuery = filterText.trim();

            if (!originalQuery) {
                filteredData = originalData;
            } else {
                // 1. Split by OR operators (comma or pipe)
                // Example: "2025-10 & G7C, Science | NOT Math" -> ["2025-10 & G7C", "Science", "NOT Math"]
                const orGroups = originalQuery.split(/,|\s*\|\s*/).filter(q => q.trim() !== '');

                filteredData = originalData.filter(item => {
                    // Item must match AT LEAST ONE OR group (OR logic)
                    return orGroups.some(orGroup => {
                        // 2. Split each OR group into AND terms (using '&' or space)
                        // Example: "2025-10 & G7C" -> ["2025-10", "G7C"]
                        const terms = orGroup.split(/&|\s+/).filter(t => t.trim() !== '');
                        
                        // An OR group must match ALL of its terms (AND logic)
                        return terms.every(term => {
                            let isNegated = false;
                            let cleanTerm = term.trim().toLowerCase();

                            // Process Negation: Check for '-' or 'NOT ' prefix
                            if (cleanTerm.startsWith('-')) {
                                isNegated = true;
                                cleanTerm = cleanTerm.substring(1).trim();
                            } else if (cleanTerm.startsWith('not ')) {
                                isNegated = true;
                                cleanTerm = cleanTerm.substring(4).trim();
                            }
                            
                            if (!cleanTerm) return true; // Ignore empty terms resulting from bad parsing

                            // Check if the item matches the term (substring check across all values)
                            const itemMatchesTerm = Object.values(item).some(value => {
                                return String(value).toLowerCase().includes(cleanTerm);
                            });
                            
                            // Apply Negation logic:
                            // If negated, return true if item DOES NOT match term.
                            // If not negated, return true if item DOES match term.
                            return isNegated ? !itemMatchesTerm : itemMatchesTerm;
                        });
                    });
                });
            }
            
            // Re-apply sort (if any) to the newly filtered data
            if (sortConfig.key) {
                const currentConfig = { ...sortConfig };
                sortConfig.key = null; 
                handleSort(currentConfig.key); 
            } else {
                renderTable(filteredData);
            }
            
            updateRecordCount(filteredData.length);
        }

        /**
         * Updates the record count display.
         * @param {number} count 
         */
        function updateRecordCount(count) {
             document.getElementById('recordCount').textContent = `Displaying ${count} of ${originalData.length} total records.`;
        }
        
        /**
         * Shows a temporary feedback message to the user.
         * @param {string} message 
         * @param {string} colorClass 
         */
        function showFeedback(message, colorClass) {
            const feedbackElement = document.getElementById('copyFeedback');
            feedbackElement.textContent = message;
            // Set opacity to 100% and apply color
            feedbackElement.className = `${colorClass} text-sm font-semibold transition duration-300 opacity-100`;

            // Hide the message after 3 seconds
            setTimeout(() => {
                feedbackElement.className = `text-sm font-semibold transition duration-300 opacity-0`;
            }, 3000);
        }
        
        /**
         * Escapes a value according to standard CSV rules.
         * @param {any} value 
         * @returns {string} The CSV-escaped string.
         */
        function csvEscape(value) {
            let str = String(value);
            
            // Step 1: Escape existing double quotes by doubling them
            str = str.replace(/"/g, '""');
            
            // Step 2: Check if wrapping is necessary (contains comma, quote, or newline)
            if (str.includes(',') || str.includes('\n') || str.includes('""')) {
                return `"${str}"`;
            }
            return str;
        }


        /**
         * Copies the current filtered and sorted data to the clipboard in CSV (comma-separated) format.
         */
        function copyFilteredRecords() {
            if (filteredData.length === 0) {
                showFeedback("No data to copy.", 'text-red-500');
                return;
            }

            const keys = Object.keys(originalData[0]);
            
            // 1. Create Header row (CSV format, delimited by comma)
            const header = keys.map(key => formatHeader(key)).join(',');
            
            // 2. Create Data rows
            const rows = filteredData.map(item => {
                // Use csvEscape for each value and join with comma
                return keys.map(key => csvEscape(item[key])).join(',');
            }).join('\n');
            
            const clipboardText = header + '\n' + rows;

            // 3. Use document.execCommand('copy') (reliable fallback for iFrames)
            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = clipboardText;
            tempTextArea.style.position = 'fixed'; // Keep it off-screen
            tempTextArea.style.left = '-9999px';
            document.body.appendChild(tempTextArea);
            tempTextArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showFeedback(`Copied ${filteredData.length} records (CSV)!`, 'text-green-600');
                } else {
                    showFeedback("Copy failed. Please try manually.", 'text-red-500');
                }
            } catch (err) {
                showFeedback("Copy failed (Error).", 'text-red-500');
                console.error('Copy to clipboard failed:', err);
            } finally {
                document.body.removeChild(tempTextArea);
            }
        }

        // --- Initialization ---
        document.addEventListener('DOMContentLoaded', fetchData);

    </script>
</body>
</html>