// Main JavaScript for RemindMe

$(document).ready(function() {
    // Initialize Summernote editor
    if ($('#content-editor').length) {
        $('#content-editor').summernote({
            height: 300,
            lang: 'es-ES',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
    }
    
    // Repeat type change handler
    $('#repeat_type').on('change', function() {
        const value = $(this).val();
        const options = $('.repeat-options');
        
        if (value === 'none') {
            options.removeClass('show');
        } else {
            options.addClass('show');
        }
    });
    
    // Add recipient button
    $('#add-recipient').on('click', function() {
        const container = $('#recipients-container');
        const html = `
            <div class="recipient-row">
                <input type="email" name="recipients[]" class="form-control" placeholder="email@ejemplo.com" required>
                <button type="button" class="btn btn-danger btn-sm remove-recipient">✕</button>
            </div>
        `;
        container.append(html);
    });
    
    // Remove recipient
    $(document).on('click', '.remove-recipient', function() {
        $(this).closest('.recipient-row').remove();
    });
    
    // Toast auto-hide
    setTimeout(function() {
        $('#toast').fadeOut();
    }, 4000);
    
    // Confirm delete
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
            e.preventDefault();
        }
    });
    
    // Toggle SMTP active
    $('.toggle-smtp').on('click', function() {
        const id = $(this).data('id');
        const btn = $(this);
        
        $.ajax({
            url: 'settings/smtp?action=toggle',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                location.reload();
            }
        });
    });
    
    // SMTP Test button
    $('#test-smtp').on('click', function() {
        const resultDiv = $('#smtp-test-result');
        const testEmail = $('#test-email').val();
        
        if (!testEmail) {
            alert('Por favor ingrese un email de prueba');
            return;
        }
        
        resultDiv.removeClass('success error').text('Enviando email de prueba...').show();
        
        $.ajax({
            url: '/shooter/api/smtp_test.php',
            type: 'POST',
            data: { test_email: testEmail },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        resultDiv.addClass('success').text(data.message);
                    } else {
                        resultDiv.addClass('error').text(data.message);
                    }
                } catch(e) {
                    resultDiv.addClass('error').text('Error al procesar la respuesta: ' + response);
                }
            },
            error: function(xhr, status, error) {
                resultDiv.addClass('error').text('Error de conexión: ' + error);
            }
        });
    });
    
    // Toggle reminder active
    $('.toggle-reminder').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        $.ajax({
            url: 'reminders?action=toggle',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                location.reload();
            }
        });
    });
    
    // Date picker for end date
    $('#end_date').on('focus', function() {
        this.showPicker?.() || this.calendar.show?.();
    });
    
    // Calculate next execution preview
    $('#repeat_type, #repeat_interval, #repeat_unit, #next_execution').on('change', calculateNextExecutionPreview);
    
    function calculateNextExecutionPreview() {
        const repeatType = $('#repeat_type').val();
        const interval = parseInt($('#repeat_interval').val()) || 1;
        const unit = $('#repeat_unit').val();
        const nextExec = $('#next_execution').val();
        
        if (!nextExec || repeatType === 'none') {
            $('#next-execution-preview').text('');
            return;
        }
        
        const date = new Date(nextExec);
        
        switch(repeatType) {
            case 'daily':
                date.setDate(date.getDate() + 1);
                break;
            case 'weekly':
                date.setDate(date.getDate() + 7);
                break;
            case 'monthly':
                date.setMonth(date.getMonth() + 1);
                break;
            case 'yearly':
                date.setFullYear(date.getFullYear() + 1);
                break;
            case 'custom':
                switch(unit) {
                    case 'minutes': date.setMinutes(date.getMinutes() + interval); break;
                    case 'hours': date.setHours(date.getHours() + interval); break;
                    case 'days': date.setDate(date.getDate() + interval); break;
                    case 'weeks': date.setDate(date.getDate() + (interval * 7)); break;
                    case 'months': date.setMonth(date.getMonth() + interval); break;
                }
                break;
        }
        
        if (repeatType !== 'none') {
            $('#next-execution-preview').text('Próxima ejecución: ' + date.toLocaleString('es-ES'));
        }
    }
});

// Delete confirmation
function confirmDelete(message) {
    return confirm(message || '¿Está seguro de que desea eliminar este elemento?');
}

// Close modal
function closeModal(modalId) {
    $('#' + modalId).removeClass('show');
}

// Open modal
function openModal(modalId) {
    $('#' + modalId).addClass('show');
}
