jQuery(document).ready(function ($) {
    const rowsPerPage = 50; // Adjust the number of rows per page
    const rows = $(".detailed-table tbody tr");
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    // Hide all rows initially
    rows.hide();

    // Show the first page of rows
    rows.slice(0, rowsPerPage).show();

    // Add pagination controls
    const pagination = $("<div class='pagination-controls'></div>");
    for (let i = 1; i <= totalPages; i++) {
        pagination.append(`<button class="pagination-btn" data-page="${i}">${i}</button>`);
    }
    $(".metafiller-detailed-table").append(pagination);

    // Handle pagination click
    $(".pagination-btn").click(function () {
        const page = $(this).data("page");
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        rows.hide().slice(start, end).show();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    // Check if the script should run on the specific page
    const targetURL = "https://metafiller.tillgreen.eu/wp-admin/admin.php?page=metafiller_dashboard&tab=meta-merge";

    if (window.location.href === targetURL) {
        const form = document.getElementById("metafiller-merge-form");
        const modal = document.getElementById("metafiller-merge-modal");
        const proceedButton = document.getElementById("metafiller-proceed");
        const cancelButton = document.getElementById("metafiller-cancel");

        if (form && modal && proceedButton && cancelButton) {
            form.addEventListener("submit", function (e) {
                e.preventDefault(); // Prevent the default form submission
                modal.style.display = "block"; // Show the modal
            });

            proceedButton.addEventListener("click", function () {
                // Manually add the submit button value
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = "metafiller_merge_meta";
                hiddenInput.value = "Merge Meta Data";
                form.appendChild(hiddenInput);

                // Submit the form
                modal.style.display = "none";
                form.submit();
            });

            cancelButton.addEventListener("click", function () {
                // Close the modal without submitting the form
                modal.style.display = "none";
            });
        } else {
            console.warn("Required elements for the script are not present on the page.");
        }
    } else {
        console.log("The script is not running on this page as it doesn't match the target URL.");
    }
});


