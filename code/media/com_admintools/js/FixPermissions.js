/**
 * @package   admintools
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.addEventListener('DOMContentLoaded', function() {
    var autoCloseElement = document.getElementById("admintools-fixpermissions-autoclose");

    if (autoCloseElement)
    {
        window.setTimeout(function() {
            parent.admintools.Controlpanel.closeModal();
        }, 3000);

        return;
    }

    document.forms.admintoolsForm.submit();
});