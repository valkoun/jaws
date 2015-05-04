<?php
/**
 * Manage files (remove, create, etc)
 *
 * @category   JawsType
 * @category   developer_feature
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_FileManagement
{
    /**
     * Removes a file or a complete directory.
     *
     * THANKS TO: http://aidan.dotgeek.org/lib/?file=function.rmdirr.php
     * @access   public
     * @param    string  $dirname File/Directory to remove
     * @return   boolean True or false on error
     */
    function FullRemoval($dirname)
    {
        // Sanity check
        if (!file_exists($dirname)) {
            return false;
        }

        // Simple delete for a file
        if (is_file($dirname)) {
            return @unlink($dirname);
        }

        // Loop through the folder
        $dir = @dir($dirname);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse
            Jaws_FileManagement::FullRemoval($dirname.'/'.$entry);
        }
        // Clean up
        $dir->close();
        return @rmdir($dirname);
    }

    /**
     * Recursive copy
     *
     * Copies a file to another location or a complete directory
     *
     * @access  public
     * @param   string  $source  File source
     * @param   string  $dest    Destination path
     * @return  bool    Returns TRUE on success, FALSE on failure
     */
    function FullCopy($source, $dest)
    {
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        Jaws_Utils::mkdir($dest);

        // Loop through the folder
        $dir = @dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== $source.'/'.$entry) {
                Jaws_FileManagement::FullCopy($source.'/'.$entry, $dest.'/'.$entry);
            }
        }
        // Clean up
        $dir->close();
        return true;
    }
}