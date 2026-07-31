<script>
(function ($) {
    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function setLoading($button, text) {
        if (!$button.length) {
            return;
        }

        if (!$button.data('original-html')) {
            $button.data('original-html', $button.html());
        }

        $button.prop('disabled', true).text(text);
    }

    function resetLoading($button) {
        if (!$button.length) {
            return;
        }

        $button.prop('disabled', false).html($button.data('original-html'));
    }

    function initChosen(scope) {
        if (typeof $.fn.chosen !== 'function') {
            return;
        }

        var $scope = scope ? $(scope) : $(document);
        $scope.find('.chosen-select').each(function () {
            var $select = $(this);

            if ($select.data('chosen')) {
                $select.chosen('destroy');
            }

            $select.chosen({
                width: '100%',
                placeholder_text_multiple: 'Select users...',
                no_results_text: 'No active user found matching'
            });
        });
    }

    function replaceFlowView(response, fallbackMessage) {
        if (response && response.view) {
            $('.man').html(response.view);
            if ($('#editMappingModal').length) {
                $('#editMappingModal').css('display', 'flex');
            }
            initChosen('.man');
            initFieldMapping('.man');
            return true;
        }

        var msg = (response && response.error) ? response.error : fallbackMessage;
        window.alert(msg);
        return false;
    }

    $(document).ready(function () {
        initChosen(document);
        initFieldMapping(document);
        initAssignedUserView(document);
    });

    function initAssignedUserView(scope) {
        var $scope = scope ? $(scope) : $(document);
        var users = window.facebookAssignedUsers || [];

        $scope.find('.campaign-checkbox').each(function () {
            var $checkbox = $(this);
            var campaignId = $checkbox.val();
            var campaignName = $checkbox.closest('.fb-assign-campaign').find('.fb-assign-campaign-name').text().trim();
            var $container = $scope.find('#input-container');

            if ($checkbox.data('fb-assign-bound')) {
                return;
            }

            $checkbox.data('fb-assign-bound', true);

            $checkbox.on('change', function () {
                var dynamicId = '#input-' + campaignId;

                if ($(this).is(':checked')) {
                    var selectOptions = users.map(function (user) {
                        return '<option value="' + user.id + '">' + user.name + '</option>';
                    }).join('');

                    $container.append(
                        '<div class="fb-assign-dynamic-item" id="input-' + campaignId + '">' +
                            '<h5>Assign users for ' + campaignName + '</h5>' +
                            '<select class="chosen-select" multiple name="fbassignleads[]" id="fbassignleads-' + campaignId + '">' +
                                selectOptions +
                            '</select>' +
                        '</div>'
                    );

                    initChosen($container);
                } else {
                    $container.find(dynamicId).remove();
                }
            });
        });
    }

    function initFieldMapping(scope) {
        var $scope = scope ? $(scope) : $(document);
        var $containers = $scope.find('.fbmappingfields');

        if (!$containers.length && $scope.hasClass('fbmappingfields')) {
            $containers = $scope;
        }

        $containers.each(function () {
            var $container = $(this);

            function checkAllFieldsMapped() {
                var allMapped = true;

                $container.find('.crm-field-select').each(function () {
                    if (!$(this).val()) {
                        allMapped = false;
                        return false;
                    }
                });

                $container.find('#map_multiple_fields').prop('disabled', !allMapped);
            }

            checkAllFieldsMapped();
        });
    }

    $(document).on('click', '#fbinitial', function (event) {
        event.preventDefault();

        var $button = $(this);
        var adid = $('#adid').val();
        var accesstoken = $('#accessToken').val();

        if (!adid || !accesstoken) {
            window.alert('Please enter both Ad ID and Access Token.');
            return;
        }

        setLoading($button, 'Loading...');

        $.ajax({
            url: "{{ url('/settings/multiple_campaigns') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                adid: adid,
                accesstoken: accesstoken
            },
            success: function (response) {
                if (response && response.view) {
                    $('.fbloginaccess').hide();
                    $('.man').html(response.view);
                    initChosen('.man');
                    return;
                }

                window.alert('Unexpected response while loading campaigns.');
            },
            error: function () {
                window.alert('Failed to load campaigns. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '#chooseAdAccounts', function (event) {
        event.preventDefault();

        var $button = $(this);
        var selectedAdAccounts = $('.ad_act_select:checked').map(function () {
            return $(this).val();
        }).get();

        if (!selectedAdAccounts.length) {
            window.alert('Please select at least one ad account.');
            return;
        }

        setLoading($button, 'Loading...');

        $.ajax({
            url: "{{ url('/settings/choose_ad_accouts') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                selectedAdAccounts: selectedAdAccounts
            },
            success: function (response) {
                replaceFlowView(response, 'Unexpected response while loading campaigns.');
            },
            error: function () {
                window.alert('Failed to load campaigns. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '#chooseCampaigns', function (event) {
        event.preventDefault();

        var $button = $(this);
        var selectedCampaigns = $('.campaign_select:checked').map(function () {
            return $(this).val();
        }).get();

        if (!selectedCampaigns.length) {
            window.alert('Please select at least one campaign.');
            return;
        }

        setLoading($button, 'Loading...');

        $.ajax({
            url: "{{ url('/settings/choose_camps') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                selectedCampaigns: selectedCampaigns
            },
            success: function (response) {
                replaceFlowView(response, 'Unexpected response while loading lead assignment.');
            },
            error: function () {
                window.alert('Failed to load lead assignment. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '.leadmapsubmit', function (event) {
        event.preventDefault();

        var $button = $(this);
        var cams = [];
        var valid = true;

        $('.fb-map-panel').each(function () {
            var campaignId = $(this).find('.leadmappingcamid').val();
            var assigned = $(this).find('.chosen-select').val() || [];
            var productId = $(this).find('.fb-product-select').val() || '';

            if (!assigned.length || !productId) {
                valid = false;
                return false;
            }

            cams.push({
                campaignId: campaignId,
                assigned: assigned,
                productId: productId
            });
        });

        if (!valid || !cams.length) {
            window.alert('Please select at least one user and one product for each campaign.');
            return;
        }

        setLoading($button, 'Saving...');

        $.ajax({
            url: "{{ url('/settings/fbassignleads') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                cams: cams
            },
            success: function (response) {
                replaceFlowView(response, 'Unexpected response while saving user assignments.');
            },
            error: function () {
                window.alert('Failed to save user assignments. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '#map_multiple_fields, #map_fb_fields', function (event) {
        event.preventDefault();

        var $button = $(this);
        var campaigns = [];

        $('.fb-map-panel').each(function () {
            var $panel = $(this);
            var campaignId = $panel.find('.map_campaigns').val();
            var fieldMappings = [];

            $panel.find('.field-mapping-row').each(function () {
                var $row = $(this);

                fieldMappings.push({
                    campaignFieldId: $row.find('.campaign-field-id').val(),
                    crmFieldKey: $row.find('.crm-field-select').val()
                });
            });

            campaigns.push({
                campaignId: campaignId,
                fieldMappings: fieldMappings
            });
        });

        setLoading($button, 'Saving...');

        $.ajax({
            url: "{{ url('/settings/mapfields') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                campaigns: campaigns
            },
            success: function (response) {
                if (response && response.view) {
                    $('.fbloginaccess').hide();
                    $('.man').html(response.view);
                    initChosen('.man');
                    return;
                }

                window.alert('Unexpected response while saving field mapping.');
            },
            error: function () {
                window.alert('Failed to save field mapping. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '#getfacebookintegration', function (event) {
        event.preventDefault();

        $.ajax({
            url: "{{ url('/settings/facebook_integration') }}",
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            success: function (response) {
                if (response && response.view) {
                    $('#fbfieldsappend').html(response.view);
                    initChosen('#fbfieldsappend');
                    return;
                }

                window.alert('Unexpected response while loading Facebook integration.');
            },
            error: function () {
                window.alert('Failed to load Facebook integration. Please try again.');
            }
        });
    });

    $(document).on('click', '#getfacebookassignlead', function (event) {
        event.preventDefault();

        $.ajax({
            url: "{{ url('/settings/viewassigned') }}",
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            success: function (response) {
                if (response && response.view) {
                    $('#fbfieldsappend').html(response.view);
                    initChosen('#fbfieldsappend');
                    return;
                }

                window.alert('Unexpected response while loading assigned users.');
            },
            error: function () {
                window.alert('Failed to load assigned users. Please try again.');
            }
        });
    });

    $(document).on('click', '#saveDataButton', function (event) {
        event.preventDefault();

        var $button = $(this);
        var selectedData = [];

        $('.campaign-checkbox:checked').each(function () {
            var campaignId = $(this).val();
            var campaignName = $(this).closest('.fb-assign-campaign').find('.fb-assign-campaign-name').text().trim();
            var selectedUsers = $('#fbassignleads-' + campaignId).val() || [];

            selectedData.push({
                campaignId: campaignId,
                campaignName: campaignName,
                users: selectedUsers
            });
        });

        if (!selectedData.length) {
            window.alert('Please select at least one campaign to assign users.');
            return;
        }

        var missingUsers = selectedData.some(function (item) {
            return !item.users.length;
        });

        if (missingUsers) {
            window.alert('Please choose at least one user for each selected campaign.');
            return;
        }

        setLoading($button, 'Saving...');

        $.ajax({
            url: "{{ url('/settings/assignUsers') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                selectedData: selectedData
            },
            success: function (response) {
                if (response && response.view) {
                    $('#fbfieldsappend').html(response.view);
                    initChosen('#fbfieldsappend');
                    initAssignedUserView('#fbfieldsappend');
                    return;
                }

                window.alert('Unexpected response while saving assignments.');
            },
            error: function () {
                window.alert('Failed to save assignments. Please try again.');
            },
            complete: function () {
                resetLoading($button);
            }
        });
    });

    $(document).on('click', '.pinnala_poganu', function (event) {
        event.preventDefault();
        window.history.back();
    });

    $(document).on('click', '.deleteintegratedcamp', function (event) {
        event.preventDefault();

        var campId = $(this).data('camp_id');

        if (!window.confirm('Are you sure you want to delete this Facebook campaign integration?')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ url('/settings/deleteintegration') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                camp_id: campId
            },
            success: function (response) {
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Failed to delete integration. Please try again.';
                window.alert(msg);
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.editintegratedcamp', function (event) {
        event.preventDefault();

        var campId = $(this).data('camp_id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ url('/settings/editfieldmaps') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken()
            },
            data: {
                camid: campId
            },
            success: function (response) {
                replaceFlowView(response, 'Unexpected response while loading edit field mapping.');
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Failed to load field mapping editor. Please try again.';
                window.alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('change', '.crm-field-select', function () {
        initFieldMapping($(this).closest('.fbmappingfields'));
    });
})(jQuery);
</script>
