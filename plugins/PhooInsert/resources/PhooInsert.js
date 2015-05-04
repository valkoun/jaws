var texarea_outer;
var GB_ROOT_DIR = "libraries/greybox/"

function SelectImage(textarea, caption, url){
    textarea_outer=textarea;
    GB_showPage(caption,url);
}

function go_select(album, picture, title, clase, psize, linked, img_src, use_plugin)
{
    if (linked == 'true') {
       is_linked = 'Yes';
    } else {
       is_linked = 'No';
    }

    if (use_plugin == 'true') {
        insertTags(textarea_outer,
               '[phoo album="'+album+'" picture="'+picture+'" title="'+title+'" class="'+clase+'" size="'+psize+'" linked="'+is_linked+'"]',
               '',
               '');
    } else {
        img = '<img src="' + img_src + '" class="' + clase + '" title="' + title + '" alt="' + title + '" />';
        if (linked == 'true') {
            img = '<a href="' + img_src + '">' + img + '</a>';
        }
        insertTags(textarea_outer, img, '', '');
    }
    GB_hide();
}

function getSizePath(filename, size) {
    path = filename.substring(0, filename.lastIndexOf('/'));
    file = filename.substring(filename.lastIndexOf('/'));

    switch(size) {
        case 'Thumb':
            return path + '/thumb' + file;
            break;
        case 'Medium':
            return path + '/medium' + file;
            break;
        default:
            return filename;
    }
}
