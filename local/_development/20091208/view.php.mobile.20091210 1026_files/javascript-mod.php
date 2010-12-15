// Javascript from Moodle modules
 // support for the templates form

function dataplusisdefined(object, variable){
    return (typeof(eval(object)[variable]) != 'undefined');
}

var dataplusCursorStart = null;
var dataplusCursorEnd = null;

function datapluscursorPosition() {
    obj = document.getElementById('id_record');

    if(dataplusisdefined(document,'selection')){
        var bookmark = document.selection.createRange().getBookmark();

        obj.selection = obj.createTextRange();  // create in textarea object and
        obj.selection.moveToBookmark(bookmark);  // match to document.selection
        obj.selLeft = obj.createTextRange(); // create textrange object

        obj.selLeft.collapse(true);           // for left amount of textarea &

        obj.selLeft.setEndPoint("EndToStart", obj.selection); // align them

        dataplusCursorStart = obj.selLeft.text.length;
        dataplusCursorEnd = obj.selLeft.text.length +  obj.selection.text.length;
    }
    else if (dataplusisdefined(obj, 'selectionStart')) {
        dataplusCursorStart = obj.selectionStart;
        dataplusCursorEnd = obj.selectionEnd;
    }
    else{
        dataplusCursorStart = null;
    }
}
    
    
function dataplusUpdateTextbox(str) {
    if (typeof(currEditor) != 'undefined' && currEditor._editMode == 'wysiwyg') {
        currEditor.focusEditor();
        currEditor.insertHTML(str);         
    }
    else if (typeof(tinyMCE) != 'undefined'){
        tinyMCE.getInstanceById('id_record').execCommand('mceInsertContent',false,str);     
    }
    else {
        var textbox    = document.getElementById('id_record');
        var currentVal = textbox.value;
        var scroll      = textbox.scrollTop;

        if (dataplusCursorStart == null){
            document.getElementById('id_record').value = currentVal + str;
            return;
        }

        start = currentVal.substring(0,dataplusCursorStart);
        end   = currentVal.substring(dataplusCursorEnd,currentVal.length);

        document.getElementById('id_record').value = start + str + end;

        dataplusCursorStart = dataplusCursorStart + str.length;
        dataplusCursorEnd   = dataplusCursorStart;

        textbox.scrollTop = scroll;

        if(dataplusisdefined(document,'selection')){
            var range = textbox.createTextRange();
            range.move("character",dataplusCursorStart);
            range.select();
        }
        else {
            textbox.select();
            textbox.setSelectionRange(dataplusCursorStart,dataplusCursorEnd);
        }
    }
}
