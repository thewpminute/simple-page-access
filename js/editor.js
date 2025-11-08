(function() {
    var el = wp.element.createElement;
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var CheckboxControl = wp.components.CheckboxControl;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Fragment = wp.element.Fragment;

    /**
     * Simple Page Access Settings Panel Component
     */
    var SimplePageAccessPanel = function() {
        // Get the current post meta
        var restrictAccess = useSelect(function(select) {
            return select('core/editor').getEditedPostAttribute('meta')['spa_restrict_access'] || false;
        });

        var allowedRoles = useSelect(function(select) {
            var roles = select('core/editor').getEditedPostAttribute('meta')['spa_allowed_roles'];
            return roles || [];
        });

        // Get the editPost function to update meta
        var editPost = useDispatch('core/editor').editPost;

        /**
         * Update restriction enabled/disabled
         */
        var updateRestrictAccess = function(value) {
            editPost({
                meta: {
                    spa_restrict_access: value
                }
            });
        };

        /**
         * Update allowed roles
         */
        var updateAllowedRoles = function(roleValue, isChecked) {
            var newRoles = allowedRoles ? [...allowedRoles] : [];

            if (isChecked) {
                // Add role if not already in array
                if (!newRoles.includes(roleValue)) {
                    newRoles.push(roleValue);
                }
            } else {
                // Remove role from array
                newRoles = newRoles.filter(function(role) {
                    return role !== roleValue;
                });
            }

            editPost({
                meta: {
                    spa_allowed_roles: newRoles
                }
            });
        };

        // Get available roles from localized data
        var availableRoles = window.spaData && window.spaData.roles ? window.spaData.roles : [];

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'simple-page-access-panel',
                title: 'Simple Page Access',
                className: 'simple-page-access-panel'
            },
            el(
                Fragment,
                {},
                el(CheckboxControl, {
                    label: 'Restrict to logged in users only',
                    checked: restrictAccess,
                    onChange: updateRestrictAccess,
                    help: 'When enabled, only logged-in users can view this content.'
                }),
                restrictAccess && el(
                    'div',
                    {
                        style: {
                            marginTop: '12px',
                            paddingTop: '12px',
                            borderTop: '1px solid #ddd'
                        }
                    },
                    el(
                        'p',
                        {
                            style: {
                                margin: '0 0 8px 0',
                                fontSize: '11px',
                                fontWeight: '500',
                                textTransform: 'uppercase',
                                color: '#757575'
                            }
                        },
                        'Allowed User Roles'
                    ),
                    el(
                        'p',
                        {
                            style: {
                                margin: '0 0 12px 0',
                                fontSize: '12px',
                                color: '#757575'
                            }
                        },
                        'Select which roles can access this content. Leave all unchecked to allow any logged-in user.'
                    ),
                    availableRoles.map(function(role) {
                        return el(CheckboxControl, {
                            key: role.value,
                            label: role.label,
                            checked: allowedRoles && allowedRoles.includes(role.value),
                            onChange: function(isChecked) {
                                updateAllowedRoles(role.value, isChecked);
                            }
                        });
                    })
                )
            )
        );
    };

    // Register the plugin
    registerPlugin('simple-page-access', {
        render: SimplePageAccessPanel,
        icon: 'lock'
    });
})();
