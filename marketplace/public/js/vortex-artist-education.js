/**
 * JavaScript for Artist Education functionality
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Variables
        let selectedWorkshopId = null;
        let selectedTimeSlot = null;
        let selectedDate = null;
        
        // Elements
        const $modal = $('#vortex-workshop-modal');
        const $modalLoading = $('.vortex-modal-loading');
        const $workshopForm = $('.vortex-workshop-form');
        const $workshopConfirmation = $('.vortex-workshop-confirmation');
        const $datePicker = $('#vortex-workshop-date');
        const $workshopList = $('.vortex-workshop-list');
        const $scheduleBtn = $('.vortex-modal-schedule');
        const $cancelBtn = $('.vortex-modal-cancel');
        const $doneBtn = $('.vortex-modal-done');
        const $closeBtn = $('.vortex-modal-close');
        
        // Set default date to today
        $datePicker.val(new Date().toISOString().split('T')[0]);
        
        // Open workshop scheduling modal
        $('#vortex-schedule-workshop-btn').on('click', function() {
            openModal();
            loadWorkshops();
        });
        
        // Close modal on click outside
        $(window).on('click', function(event) {
            if ($(event.target).is($modal)) {
                closeModal();
            }
        });
        
        // Close modal with close button
        $closeBtn.on('click', function() {
            closeModal();
        });
        
        // Close modal with cancel button
        $cancelBtn.on('click', function() {
            closeModal();
        });
        
        // Close modal with done button
        $doneBtn.on('click', function() {
            closeModal();
            // Reload the page to show the newly scheduled workshop
            window.location.reload();
        });
        
        // Date picker change
        $datePicker.on('change', function() {
            selectedDate = $(this).val();
            loadWorkshops();
        });
        
        // Schedule button click
        $scheduleBtn.on('click', function() {
            if (selectedWorkshopId && selectedTimeSlot && selectedDate) {
                scheduleWorkshop();
            }
        });
        
        // Cancel workshop
        $('.vortex-cancel-workshop').on('click', function() {
            const scheduleId = $(this).data('schedule-id');
            if (confirm(vortexEducation.cancel_confirmation)) {
                cancelWorkshop(scheduleId);
            }
        });
        
        // Function to open the modal
        function openModal() {
            resetModal();
            $modal.fadeIn(300);
            $('body').addClass('vortex-modal-open');
        }
        
        // Function to close the modal
        function closeModal() {
            $modal.fadeOut(300);
            $('body').removeClass('vortex-modal-open');
            setTimeout(resetModal, 300);
        }
        
        // Function to reset the modal state
        function resetModal() {
            $modalLoading.show();
            $workshopForm.hide();
            $workshopConfirmation.hide();
            $scheduleBtn.show().prop('disabled', true);
            $cancelBtn.show();
            $doneBtn.hide();
            
            selectedWorkshopId = null;
            selectedTimeSlot = null;
            $workshopList.empty();
        }
        
        // Function to load available workshops
        function loadWorkshops() {
            $modalLoading.show();
            $workshopForm.hide();
            $workshopList.empty();
            
            $.ajax({
                url: vortexEducation.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_available_workshops',
                    nonce: vortexEducation.nonce,
                    date: selectedDate || $datePicker.val()
                },
                success: function(response) {
                    if (response.success) {
                        renderWorkshops(response.data.workshops);
                        $modalLoading.hide();
                        $workshopForm.show();
                    } else {
                        showError(response.data.message || 'Error loading workshops');
                    }
                },
                error: function() {
                    showError('Connection error. Please try again.');
                }
            });
        }
        
        // Function to render workshops
        function renderWorkshops(workshops) {
            if (!workshops || workshops.length === 0) {
                $workshopList.html('<p class="vortex-no-workshops">No workshops available on this date. Please select another date.</p>');
                return;
            }
            
            workshops.forEach(function(workshop) {
                const $workshopCard = $('<div>', {
                    class: 'vortex-workshop-card',
                    'data-id': workshop.id
                });
                
                const $workshopImage = $('<div>', {
                    class: 'vortex-workshop-image'
                }).append($('<img>', {
                    src: workshop.image,
                    alt: workshop.title
                }));
                
                const $workshopInfo = $('<div>', {
                    class: 'vortex-workshop-info'
                });
                
                const $workshopTitle = $('<h4>', {
                    class: 'vortex-workshop-title',
                    text: workshop.title
                });
                
                const $workshopDetails = $('<div>', {
                    class: 'vortex-workshop-details'
                }).html(
                    '<span><i class="dashicons dashicons-businessman"></i> ' + workshop.instructor + '</span>' +
                    '<span><i class="dashicons dashicons-tag"></i> ' + workshop.category + '</span>' +
                    '<span><i class="dashicons dashicons-hourglass"></i> ' + workshop.duration + ' hours</span>' +
                    '<span><i class="dashicons dashicons-chart-bar"></i> ' + workshop.level + '</span>'
                );
                
                const $workshopTimes = $('<div>', {
                    class: 'vortex-workshop-times'
                });
                
                // Add time slots
                if (workshop.available_times && workshop.available_times.length > 0) {
                    workshop.available_times.forEach(function(timeSlot) {
                        const $timeSlot = $('<div>', {
                            class: 'vortex-time-slot' + (timeSlot.available ? '' : ' unavailable'),
                            text: timeSlot.time,
                            'data-time': timeSlot.time
                        });
                        
                        if (timeSlot.available) {
                            $timeSlot.on('click', function() {
                                $('.vortex-time-slot').removeClass('selected');
                                $(this).addClass('selected');
                                $('.vortex-workshop-card').removeClass('selected');
                                $(this).closest('.vortex-workshop-card').addClass('selected');
                                
                                selectedWorkshopId = workshop.id;
                                selectedTimeSlot = timeSlot.time;
                                
                                // Enable schedule button
                                $scheduleBtn.prop('disabled', false);
                            });
                        }
                        
                        $workshopTimes.append($timeSlot);
                    });
                } else {
                    $workshopTimes.html('<p>No available times</p>');
                }
                
                $workshopInfo.append($workshopTitle, $workshopDetails, $workshopTimes);
                $workshopCard.append($workshopImage, $workshopInfo);
                $workshopList.append($workshopCard);
            });
        }
        
        // Function to schedule a workshop
        function scheduleWorkshop() {
            $scheduleBtn.prop('disabled', true).text('Scheduling...');
            
            $.ajax({
                url: vortexEducation.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_schedule_workshop',
                    nonce: vortexEducation.nonce,
                    workshop_id: selectedWorkshopId,
                    date: selectedDate || $datePicker.val(),
                    time_slot: selectedTimeSlot
                },
                success: function(response) {
                    $scheduleBtn.text('Schedule');
                    
                    if (response.success) {
                        showConfirmation(response.data);
                    } else {
                        showError(response.data.message || 'Error scheduling workshop');
                        $scheduleBtn.prop('disabled', false);
                    }
                },
                error: function() {
                    showError('Connection error. Please try again.');
                    $scheduleBtn.prop('disabled', false).text('Schedule');
                }
            });
        }
        
        // Function to cancel a workshop
        function cancelWorkshop(scheduleId) {
            $.ajax({
                url: vortexEducation.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_cancel_workshop',
                    nonce: vortexEducation.nonce,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Error cancelling workshop');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                }
            });
        }
        
        // Function to show confirmation
        function showConfirmation(data) {
            const workshop = data.workshop;
            const formattedDate = new Date(data.date).toLocaleDateString();
            
            $workshopForm.hide();
            $modalLoading.hide();
            
            // Build confirmation details
            const confirmationHTML = `
                <p><strong>Workshop:</strong> ${workshop.title}</p>
                <p><strong>Instructor:</strong> ${workshop.instructor}</p>
                <p><strong>Date:</strong> ${formattedDate}</p>
                <p><strong>Time:</strong> ${data.time_slot}</p>
                <p><strong>Duration:</strong> ${workshop.duration} hours</p>
                <p><strong>Hours Remaining:</strong> ${data.hours_remaining}</p>
            `;
            
            $('.vortex-confirmation-details').html(confirmationHTML);
            $workshopConfirmation.show();
            
            // Update buttons
            $scheduleBtn.hide();
            $cancelBtn.hide();
            $doneBtn.show();
        }
        
        // Function to show error
        function showError(message) {
            alert(message);
            $modalLoading.hide();
            $workshopForm.show();
        }
    });

})(jQuery); 