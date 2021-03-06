// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * JavaScript library for the quiz module editing interface.
 *
 * @package mod_quiz
 * @copyright 2008 Olli Savolainen
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Initialise everything on the quiz edit/order and paging page.
var quiz_edit = {};
function quiz_edit_init() {

    // Add random question dialogue --------------------------------------------
    
    quiz_edit.randomquestiondialog = new YAHOO.widget.Dialog('randomquestiondialog', {
            modal: true,
            width: '100%',
            iframe: true,
            zIndex: 1000, // zIndex must be way above 99 to be above the active quiz tab
            fixedcenter: true,
            visible: false,
            close: true,
            constraintoviewport: true,
            postmethod: 'form'
    });
    quiz_edit.randomquestiondialog.render();

    // Show the form on button click.
    YAHOO.util.Event.addListener(quiz_edit_config.dialoglisteners, 'click', function(e) {
        // Transfer the page number from the button form to the pop-up form.
        var addrandombutton = YAHOO.util.Event.getTarget(e);
        var addpagehidden = YAHOO.util.Dom.getElementsByClassName('addonpage_formelement', 'input', addrandombutton.form);
        document.getElementById('rform_qpage').value = addpagehidden.value;

        // Show the dialogue and stop the default action.
        quiz_edit.randomquestiondialog.show();
        YAHOO.util.Event.stopEvent(e);
    });

    // Make escape close the dialogue.
    quiz_edit.randomquestiondialog.cfg.setProperty('keylisteners', [new YAHOO.util.KeyListener(
            document, {keys:[27]}, function(types, args, obj) { quiz_edit.randomquestiondialog.hide();
    })]);

    // Make the form cancel button close the dialogue.
    YAHOO.util.Event.addListener('id_cancel', 'click', function(e) {
        quiz_edit.randomquestiondialog.hide();
        YAHOO.util.Event.preventDefault(e);
    });

    // Repaginate dialogue -----------------------------------------------------
    quiz_edit.repaginatedialog = new YAHOO.widget.Dialog('repaginatedialog', {
            modal: true,
            width: '30em',
            iframe: true,
            zIndex: 1000,
            context: ['repaginatecommand', 'tr', 'br', ['beforeShow']],
            visible: false,
            close: true,
            constraintoviewport: true,
            postmethod: 'form'
    });
    quiz_edit.repaginatedialog.render();

    // Show the form on button click.
    YAHOO.util.Event.addListener('repaginatecommand', 'click', function() {
        quiz_edit.repaginatedialog.show();
    });

    // Reposition the dialogue when the window resizes. For some reason this was not working automatically.
    YAHOO.widget.Overlay.windowResizeEvent.subscribe(function() {
      quiz_edit.repaginatedialog.cfg.setProperty('context', ['repaginatecommand', 'tr', 'br', ['beforeShow']]);
    });

    // Make escape close the dialogue.
    quiz_edit.repaginatedialog.cfg.setProperty('keylisteners', [new YAHOO.util.KeyListener(
            document, {keys:[27]}, function(types, args, obj) { quiz_edit.repaginatedialog.hide();
    })]);

    // Nasty hack, remove once the YUI bug causing MDL-17594 is fixed.
    // https://sourceforge.net/tracker/index.php?func=detail&aid=2493426&group_id=165715&atid=836476
    var elementcauseinglayoutproblem = document.getElementById('_yuiResizeMonitor');
    if (elementcauseinglayoutproblem) {
        elementcauseinglayoutproblem.style.left = '0px';
    }
}

// Initialise everything on the quiz settings form.
function quiz_settings_init() {
    var repaginatecheckbox = document.getElementById('id_repaginatenow');
    if (!repaginatecheckbox) {
        // This checkbox does not appear on the create new quiz form.
        return;
    }
    var qppselect = document.getElementById('id_questionsperpage');
    var qppinitialvalue = qppselect.value;
    YAHOO.util.Event.addListener([qppselect, 'id_shufflequestions'] , 'change', function() {
        setTimeout(function() { // Annoyingly, this handler runs before the formlib disabledif code, hence the timeout.
            if (!repaginatecheckbox.disabled) {
                repaginatecheckbox.checked = qppselect.value != qppinitialvalue;
            }
        }, 50);
    });
}

// Depending on which page this is, do the appropriate initialisation.
function quiz_edit_generic_init() {
    switch (document.body.id) {
    case 'mod-quiz-edit':
        quiz_edit_init();
        break;
    case 'mod-quiz-mod':
        quiz_settings_init();
    }
}
YAHOO.util.Event.onDOMReady(quiz_edit_generic_init);
