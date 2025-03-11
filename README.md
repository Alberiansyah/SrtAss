# SrtAss

**SrtAss** is a web-based application designed to handle subtitle files in the `SRT` and `ASS` formats. The main functionality includes the ability to upload subtitle files, process them, make word replacements based on a custom dictionary, and download the modified subtitles in various formats. The application also supports adding, removing, and modifying the dictionary entries, making it flexible for users working with multiple subtitle languages or styles.

## Features

- **File Upload**: Upload `SRT` or `ASS` subtitle files.
- **Dictionary Management**:
  - Add new words to the dictionary to automatically replace terms in the subtitles.
  - Remove existing dictionary entries and update the subtitle text accordingly.
  - Manage dictionary items with easy-to-use interface.
- **Word Replacement**: Replace words in the subtitle file using the dictionary, and highlight replacements in the subtitles.
- **Download Subtitles**: Download the processed subtitle file in either `SRT` or `ASS` format, with all replacements applied.
- **Clear Session**: Clear the session to start fresh with new subtitle files or dictionary entries.

## Setup Instructions

1. **Create the Dictionary File**:
   First, ensure that a `dictionary.json` file exists in the `content/json/` directory. This file will store all the words and their replacements for use in the subtitle files. Example content for the file:

   ```json
   {
     "Hey how's it going": "Good morning"
   }
   ```

2. **Upload Subtitle Files**:
   You can upload your subtitle files (`SRT` or `ASS` format) using the upload interface. The application will automatically process these files, parse their content, and make it available for editing or downloading.

3. **Dictionary Management**:

   - **Add to Dictionary**: You can add new word pairs (e.g., replace `Hey` with `Hello`) through the form on the interface.
   - **Remove from Dictionary**: If you no longer need a replacement, simply remove the entry, and the changes will reflect in the subtitles automatically.

4. **Clear Session**:
   If you want to start fresh or reset the dictionary word, you can clear the session, which will delete any files and dictionary changes.

5. **Download Processed Subtitles**:
   After processing the subtitles, you can download them in your desired format (`SRT` or `ASS`). The subtitle text will reflect the word replacements based on the current dictionary.

## Troubleshooting

- **Dictionary Not Working**: If the dictionary is not being applied, try clearing the session and re-uploading the subtitle file. This will ensure that the dictionary is properly loaded and applied to the subtitle content.
- **Empty Dictionary**: If the dictionary file is missing or empty, the application will still function but without any word replacements.

## Folder Structure

- `content/json/`: Store the dictionary file (`dictionary.json`) here.
- `uploads/`: Folder to store uploaded subtitle files (temporary).

## Technologies Used

- PHP for server-side logic
- Session handling to store and manage subtitle and dictionary data
- JSON for dictionary storage

## License

This project is open-source. Feel free to fork and modify as needed for your personal use.
