jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.claude-tab-item').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active states
        $('.claude-tab-item').removeClass('active');
        $(this).addClass('active');
        
        // Show selected content
        $('.claude-tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
    });

    // Password visibility toggle
    $('.claude-toggle-password').on('click', function() {
        const $input = $(this).siblings('input');
        const $icon = $(this).find('.dashicons');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // File extensions management
    const $extensionInput = $('#claude_file_extension_input');
    const $extensionsContainer = $('#extensions-container');
    const $hiddenInput = $('#claude_allowed_file_extensions');

    function updateExtensionsList() {
        const extensions = [];
        $extensionsContainer.find('.claude-tag').each(function() {
            extensions.push($(this).data('value'));
        });
        $hiddenInput.val(extensions.join(','));
    }

    $('#add-extension').on('click', function() {
        const extension = $extensionInput.val().trim().toLowerCase();
        if (extension && !$extensionsContainer.find(`[data-value="${extension}"]`).length) {
            const $tag = $(`
                <span class="claude-tag" data-value="${extension}">
                    ${extension}
                    <button type="button" class="claude-remove-tag">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </span>
            `);
            $extensionsContainer.append($tag);
            $extensionInput.val('');
            updateExtensionsList();
        }
    });

    $extensionsContainer.on('click', '.claude-remove-tag', function() {
        $(this).parent().remove();
        updateExtensionsList();
    });

    $extensionInput.on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#add-extension').click();
        }
    });

    // Auto purge toggle
    $('#claude_auto_purge_enabled').on('change', function() {
        const $daysInput = $('#claude_auto_purge_days');
        $daysInput.prop('disabled', !$(this).is(':checked'));
    });

    // API Testing
    function testAPI(endpoint, data, resultId) {
        const $result = $(`#${resultId}`);
        $result.html('<div class="claude-loading">' + claudeSettings.saving + '</div>');

        $.ajax({
            url: claudeSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: endpoint,
                nonce: claudeSettings.nonce,
                ...data
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="claude-success">' + response.data.message + '</div>');
                } else {
                    $result.html('<div class="claude-error">' + response.data.message + '</div>');
                }
            },
            error: function(xhr) {
                let msg = claudeSettings.error;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                $result.html('<div class="claude-error">' + msg + '</div>');
            }
        });
    }

    // Test Claude API
    $('#test-claude-api').on('click', function() {
        const apiKey = $('#claude_api_key').val();
        testAPI('test_claude_api', { api_key: apiKey }, 'api-test-result');
    });

    // Test GitHub API
    $('#test-github-api').on('click', function() {
        testGithubConnection();
    });

    // Odświeżanie modeli
    $('#refresh-models').on('click', function() {
        const $button = $(this);
        const $icon = $button.find('.dashicons');
        const $footer = $('.claude-sidebar-footer');
        
        // Wyłącz przycisk i pokaż animację
        $button.prop('disabled', true);
        $icon.addClass('claude-spinning');
        
        // Pokaż komunikat o odświeżaniu
        $footer.find('p').html('Odświeżanie modeli...<br><span class="claude-loading-text">Proszę czekać</span>');

        $.ajax({
            url: claudeSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'refresh_claude_models',
                nonce: claudeSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Pokaż sukces
                    $footer.find('p').html(
                        'Ostatnia aktualizacja modeli:<br>' +
                        '<strong>' + response.data.last_update + '</strong>'
                    );
                    
                    // Odśwież stronę po krótkim opóźnieniu
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    // Pokaż błąd
                    $footer.find('p').html(
                        'Błąd odświeżania modeli:<br>' +
                        '<strong class="claude-error">' + (response.data.message || 'Nieznany błąd') + '</strong>'
                    );
                    
                    // Przywróć przycisk
                    $button.prop('disabled', false);
                    $icon.removeClass('claude-spinning');
                }
            },
            error: function(xhr, status, error) {
                // Pokaż błąd połączenia
                $footer.find('p').html(
                    'Błąd połączenia:<br>' +
                    '<strong class="claude-error">Nie można połączyć się z serwerem</strong>'
                );
                
                // Przywróć przycisk
                $button.prop('disabled', false);
                $icon.removeClass('claude-spinning');
                
                console.error('Błąd odświeżania modeli:', error);
            }
        });
    });

    // Wysyłanie formularza
    $('#claude-settings-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $icon = $submitButton.find('.dashicons');
        const originalText = $submitButton.html();

        $submitButton.prop('disabled', true);
        $submitButton.html('<span class="dashicons dashicons-update claude-spinning"></span>' + claudeSettings.saving);

        // Pobierz nonce z ukrytego pola formularza
        const formData = new FormData(this);
        formData.append('claude_settings_nonce', $('#claude_settings_nonce').val());

        // Dodaj kontener na komunikaty na górze formularza, jeśli nie istnieje
        if (!$('#claude-settings-form .claude-form-message').length) {
            $('#claude-settings-form').prepend('<div class="claude-form-message" style="display:none;margin-bottom:20px;"></div>');
        }

        $.ajax({
            url: claudeSettings.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Usuń stare komunikaty
                $form.find('.claude-form-message').remove();
                if (response.success) {
                    $submitButton.html('<span class="dashicons dashicons-yes-alt"></span>' + claudeSettings.saved);
                    // Komunikat: tylko napis na zielono, tło białe
                    let $msg = $('<span class="claude-form-message" style="margin-right:16px;color:#27ae60;background:#fff;padding:10px 18px;border-radius:6px;font-weight:600;display:inline-block;vertical-align:middle;border:1px solid #27ae60;">Ustawienia zostały zapisane!</span>');
                    $submitButton.before($msg);
                    setTimeout(() => {
                        $msg.fadeOut(400, function(){ $(this).remove(); });
                        $submitButton.html(originalText);
                        $submitButton.prop('disabled', false);
                    }, 2000);
                } else {
                    // Komunikat: tylko napis na czerwono, tło białe
                    let $msg = $('<span class="claude-form-message" style="margin-right:16px;color:#e74c3c;background:#fff;padding:10px 18px;border-radius:6px;font-weight:600;display:inline-block;vertical-align:middle;border:1px solid #e74c3c;">' + (response.data.message || claudeSettings.error) + '</span>');
                    $submitButton.before($msg);
                    setTimeout(() => {
                        $msg.fadeOut(400, function(){ $(this).remove(); });
                        $submitButton.html(originalText);
                        $submitButton.prop('disabled', false);
                    }, 3000);
                }
            },
            error: function() {
                $form.find('.claude-form-message').remove();
                let $msg = $('<span class="claude-form-message" style="margin-right:16px;color:#e74c3c;background:#fff;padding:10px 18px;border-radius:6px;font-weight:600;display:inline-block;vertical-align:middle;border:1px solid #e74c3c;">' + claudeSettings.error + '</span>');
                $submitButton.before($msg);
                setTimeout(() => {
                    $msg.fadeOut(400, function(){ $(this).remove(); });
                    $submitButton.html(originalText);
                    $submitButton.prop('disabled', false);
                }, 3000);
            }
        });
    });

    // Notice dismissal
    $('.claude-notice-dismiss').on('click', function() {
        $(this).closest('.claude-notice').fadeOut();
    });

    // Walidacja klucza API
    $('#claude_api_key').on('input', function() {
        const value = $(this).val();
        if (value && value.length < 20) {
            showValidationMessage($(this), 'warning', 'Klucz API wydaje się być zbyt krótki');
        } else {
            hideValidationMessage($(this));
        }
    });

    // Walidacja tokenu GitHub
    $('#claude_github_token').on('input', function() {
        const value = $(this).val();
        if (value && value.length < 20) {
            showValidationMessage($(this), 'warning', 'Token GitHub wydaje się być zbyt krótki');
        } else {
            hideValidationMessage($(this));
        }
    });
    
    // Walidacja rozmiaru pliku
    $('#claude_max_file_size').on('input', function() {
        const value = parseInt($(this).val());
        if (isNaN(value) || value < 1) {
            showValidationMessage($(this), 'error', 'Rozmiar pliku musi być większy niż 1MB');
        } else if (value > 100) {
            showValidationMessage($(this), 'warning', 'Duży rozmiar pliku może wpłynąć na wydajność');
        } else {
            hideValidationMessage($(this));
        }
    });

    // Funkcja pomocnicza do wyświetlania komunikatów walidacji
    function showValidationMessage($input, type, message) {
        let $message = $input.next('.validation-message');
        if (!$message.length) {
            $message = $('<div class="validation-message"></div>').insertAfter($input);
        }
        $message
            .removeClass('success warning error')
            .addClass(type)
            .text(message)
            .show();
    }

    // Funkcja pomocnicza do ukrywania komunikatów walidacji
    function hideValidationMessage($input) {
        $input.next('.validation-message').hide();
    }

    // Animacja przy zapisywaniu ustawień
    $('form').on('submit', function() {
        const $submitButton = $(this).find('.button-primary');
        const originalText = $submitButton.text();
        $submitButton
            .prop('disabled', true)
            .text('Zapisywanie...')
            .addClass('updating-message');
        // Przywróć przycisk po 2 sekundach (w przypadku błędu)
        setTimeout(function() {
            $submitButton
                .prop('disabled', false)
                .text(originalText)
                .removeClass('updating-message');
        }, 2000);
    });

    // Podpowiedzi dla pól
    $('.field-wrapper input, .field-wrapper select').on('focus', function() {
        const $description = $(this).closest('.field-wrapper').find('.description');
        if ($description.length) {
            $description.addClass('highlight');
        }
    }).on('blur', function() {
        const $description = $(this).closest('.field-wrapper').find('.description');
        if ($description.length) {
            $description.removeClass('highlight');
        }
    });

    // Animacja "success" po zapisie
    function showSuccessAnimation() {
        const saveBtn = document.querySelector('.button-primary');
        if (!saveBtn) return;
        saveBtn.classList.add('success');
        saveBtn.textContent = '✔ Zapisano!';
        setTimeout(() => {
            saveBtn.classList.remove('success');
            saveBtn.textContent = 'Zapisz zmiany';
        }, 1500);
    }

    // Obsługa formularza ustawień
    const settingsForm = document.querySelector('.claude-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            setTimeout(showSuccessAnimation, 100); // Po zapisie
        });
    }

    // Tooltipy (jeśli są w HTML)
    document.querySelectorAll('.tooltip').forEach(el => {
        el.addEventListener('focus', function() {
            const tip = el.querySelector('.tooltip-text');
            if (tip) tip.style.opacity = 1;
        });
        el.addEventListener('blur', function() {
            const tip = el.querySelector('.tooltip-text');
            if (tip) tip.style.opacity = 0;
        });
    });

    // Tryb ciemny (przełącznik)
    const darkModeToggle = document.querySelector('#claude-dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (darkModeToggle.checked) {
                document.body.classList.add('claude-dark-mode');
                localStorage.setItem('claude-dark-mode', '1');
            } else {
                document.body.classList.remove('claude-dark-mode');
                localStorage.setItem('claude-dark-mode', '0');
            }
        });
        // Inicjalizacja na starcie
        if (localStorage.getItem('claude-dark-mode') === '1') {
            darkModeToggle.checked = true;
            document.body.classList.add('claude-dark-mode');
        }
    }

    // Subtelna animacja focus na polach
    const fields = document.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
        field.addEventListener('focus', () => {
            field.classList.add('focused');
        });
        field.addEventListener('blur', () => {
            field.classList.remove('focused');
        });
    });

    function testGithubConnection() {
        const token = document.getElementById('claude_github_token').value;
        console.log('Testing GitHub connection with token:', token);

        if (!token) {
            alert('Wprowadź token GitHub');
            return;
        }

        const data = {
            action: 'test_github_connection',
            nonce: claudeSettings.nonce,
            api_key: token,
            api_type: 'github'
        };

        console.log('Sending AJAX request with data:', data);

        fetch(claudeSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('GitHub connection test response:', data);
            if (data.success) {
                alert(data.data && data.data.message ? data.data.message : 'Połączenie z GitHubem udane!');
            } else {
                alert(data.data && data.data.message ? data.data.message : 'Błąd połączenia z GitHubem');
            }
        })
        .catch(error => {
            console.error('GitHub connection test error:', error);
            alert('Wystąpił błąd podczas testowania połączenia');
        });
    }
});

// --- Nowoczesny UX dla settings-modern.php ---
document.addEventListener('DOMContentLoaded', function() {
  // Przełączanie widoczności haseł
  document.querySelectorAll('.toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      const icon = btn.querySelector('.dashicons');
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('dashicons-visibility');
          icon.classList.add('dashicons-hidden');
        } else {
          input.type = 'password';
          icon.classList.remove('dashicons-hidden');
          icon.classList.add('dashicons-visibility');
        }
      }
    });
  });

  // Animacja "Zapisano!"
  const form = document.querySelector('.claude-modern-settings-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const success = form.querySelector('.save-success');
      if (success) {
        setTimeout(function() {
          success.classList.add('active');
        }, 200);
        setTimeout(function() {
          success.classList.remove('active');
        }, 2200);
      }
    });
  }

  // Tryb ciemny (przełącznik, localStorage)
  function setDarkMode(on) {
    document.body.classList.toggle('dark-mode', on);
    const wrap = document.querySelector('.claude-modern-settings-wrap');
    if (wrap) wrap.classList.toggle('dark-mode', on);
    localStorage.setItem('claude_dark_mode', on ? '1' : '0');
  }
  // Dodaj przełącznik trybu ciemnego jeśli nie istnieje
  if (!document.getElementById('claude-darkmode-toggle')) {
    const toggle = document.createElement('button');
    toggle.id = 'claude-darkmode-toggle';
    toggle.type = 'button';
    toggle.className = 'icon-btn';
    toggle.title = 'Przełącz tryb ciemny';
    toggle.innerHTML = '<span class="dashicons dashicons-lightbulb"></span>';
    const header = document.querySelector('.claude-modern-header');
    if (header) header.appendChild(toggle);
    toggle.addEventListener('click', function() {
      setDarkMode(!document.body.classList.contains('dark-mode'));
    });
  }
  // Ustaw tryb ciemny z localStorage
  if (localStorage.getItem('claude_dark_mode') === '1') {
    setDarkMode(true);
  }

  // Mikrointerakcje: focus na polu = podświetlenie field-group
  document.querySelectorAll('.field-group input, .field-group select').forEach(function(el) {
    el.addEventListener('focus', function() {
      el.closest('.field-group').classList.add('focused');
    });
    el.addEventListener('blur', function() {
      el.closest('.field-group').classList.remove('focused');
    });
  });
}); 