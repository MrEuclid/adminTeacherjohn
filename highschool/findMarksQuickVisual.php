<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connect_db_euclid_pio.php" ; 

$date = date('d-M-Y') ;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Data Visual</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {<?php 
require_once "../authCheckPIO.php";
restrictToAdmin();
include "../connect_db_euclid_pio.php" ; 

$date = date('d-M-Y') ;
?>


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
            API Data Viewer Visualisation
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
        
        // --- Configuration: Date Keys ---
        // CRITICAL: All columns containing YYYY-MM-DD dates must be listed here.
        // I've included common date column names. Add any others specific to your 'findMarks' API.
        const DATE_KEYS = ['date', 'birth_date', 'created_at', 'Date', 'exam_date']; 
        
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
            //    const response = await fetch('visualisationSubjectMonthTotalQuickVisual.php');
                  const response = await fetch('hsTests.php');
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
                
                // Generate headers once data is loaded
                generateHeaders(originalData[0]);

                renderTable(filteredData);
                updateRecordCount(filteredData.length);

            } catch (error) {
                console.error("Error fetching data:", error);
                document.getElementById('statusMessage').innerHTML = `<p class="text-red-500 font-semibold p-10">Failed to load data: ${error.message}. Please check the console for details.</p>`;
            }
        }

        /**
         * Generates the table headers separately.
         */
        function generateHeaders(firstRecord) {
            const tableHead = document.getElementById('tableHead');
            tableHead.innerHTML = ''; 

            const keys = Object.keys(firstRecord);
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

        /**
         * Renders the table content.
         */
        function renderTable(data) {
            const tableBody = document.getElementById('tableBody');
            
            // Generate Body
            if (data.length === 0) {
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
            
            // Update header arrows
            updateHeaderArrows();
        }

        function formatHeader(key) {
            return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1').trim();
        }
        
        function formatValue(value) {
            if (typeof value === 'number' && (value >= 1000 || value <= -1000)) {
                // Simple currency/large number formatting
                return `$${value.toLocaleString()}`;
            }
            return value;
        }

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
                
                let comparison = 0;

                // --- FIX: Explicit Date Comparison ---
                if (DATE_KEYS.includes(key)) {
                    // Use Date objects for robust chronological comparison
                    const dateA = new Date(valA);
                    const dateB = new Date(valB);

                    // Check for invalid dates
                    if (!isNaN(dateA.getTime()) && !isNaN(dateB.getTime())) {
                        if (dateA.getTime() > dateB.getTime()) comparison = 1;
                        else if (dateA.getTime() < dateB.getTime()) comparison = -1;
                    } else {
                        // Fallback to string sort if parsing fails
                        const strA = String(valA).toLowerCase();
                        const strB = String(valB).toLowerCase();
                        if (strA > strB) comparison = 1;
                        else if (strA < strB) comparison = -1;
                    }
                } else {
                    // --- Standard Numeric/String Comparison ---
                    const numA = parseFloat(valA);
                    const numB = parseFloat(valB);

                    // Check if both values are valid numbers
                    if (!isNaN(numA) && isFinite(numA) && !isNaN(numB) && isFinite(numB)) {
                        if (numA > numB) comparison = 1;
                        else if (numA < numB) comparison = -1;
                    } else {
                        // Fallback to string comparison
                        const strA = String(valA).toLowerCase();
                        const strB = String(valB).toLowerCase();
                        if (strA > strB) comparison = 1;
                        else if (strA < strB) comparison = -1;
                    }
                }

                // Apply direction multiplier
                return direction === 'asc' ? comparison : comparison * -1;
            });

            filteredData = sortedData;
            renderTable(filteredData);
        }

        /**
         * Handles filtering logic based on the input text.
         */
        function handleFilter(filterText) {
            const originalQuery = filterText.trim();

            if (!originalQuery) {
                filteredData = originalData;
            } else {
                const orGroups = originalQuery.split(/,|\s*\|\s*/).filter(q => q.trim() !== '');

                filteredData = originalData.filter(item => {
                    return orGroups.some(orGroup => {
                        const terms = orGroup.split(/&|\s+/).filter(t => t.trim() !== '');
                        
                        return terms.every(term => {
                            let cleanTerm = term.trim().toLowerCase();
                            
                            // Numeric Comparisons
                            const comparisonMatch = cleanTerm.match(/^([<>]=?)(-?[\d.]+)$/); 
                            if (comparisonMatch) {
                                const operator = comparisonMatch[1];
                                const targetVal = parseFloat(comparisonMatch[2]);
                                return Object.values(item).some(value => {
                                    const numVal = parseFloat(value);
                                    if (isNaN(numVal)) return false; 
                                    switch (operator) {
                                        case '>': return numVal > targetVal;
                                        case '<': return numVal < targetVal;
                                        case '>=': return numVal >= targetVal;
                                        case '<=': return numVal <= targetVal;
                                        default: return false;
                                    }
                                });
                            }

                            // Text Search
                            let isNegated = false;
                            if (cleanTerm.startsWith('-')) {
                                isNegated = true;
                                cleanTerm = cleanTerm.substring(1).trim();
                            } else if (cleanTerm.startsWith('not ')) {
                                isNegated = true;
                                cleanTerm = cleanTerm.substring(4).trim();
                            }
                            
                            if (!cleanTerm) return true;

                            const itemMatchesTerm = Object.values(item).some(value => {
                                return String(value).toLowerCase().includes(cleanTerm);
                            });
                            
                            return isNegated ? !itemMatchesTerm : itemMatchesTerm;
                        });
                    });
                });
            }
            
            // Re-apply sort
            if (sortConfig.key) {
                const currentConfig = { ...sortConfig };
                sortConfig.key = null; 
                handleSort(currentConfig.key); 
            } else {
                renderTable(filteredData);
            }
            
            updateRecordCount(filteredData.length);
        }

        function updateRecordCount(count) {
             document.getElementById('recordCount').textContent = `Displaying ${count} of ${originalData.length} total records.`;
        }
        
        function showFeedback(message, colorClass) {
            const feedbackElement = document.getElementById('copyFeedback');
            feedbackElement.textContent = message;
            feedbackElement.className = `${colorClass} text-sm font-semibold transition duration-300 opacity-100`;

            setTimeout(() => {
                feedbackElement.className = `text-sm font-semibold transition duration-300 opacity-0`;
            }, 3000);
        }
        
        function csvEscape(value) {
            let str = String(value);
            str = str.replace(/"/g, '""');
            if (str.includes(',') || str.includes('\n') || str.includes('""')) {
                return `"${str}"`;
            }
            return str;
        }

        function copyFilteredRecords() {
            if (filteredData.length === 0) {
                showFeedback("No data to copy.", 'text-red-500');
                return;
            }

            const keys = Object.keys(originalData[0]);
            const header = keys.map(key => formatHeader(key)).join(',');
            const rows = filteredData.map(item => {
                return keys.map(key => csvEscape(item[key])).join(',');
            }).join('\n');
            
            const clipboardText = header + '\n' + rows;

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