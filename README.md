# SrtAss

**SrtAss** is a web-based application designed to handle subtitle files in the `SRT` and `ASS` formats. The main functionality includes the ability to upload subtitle files, process them, make word replacements based on a custom dictionary, and download the modified subtitles in various formats. The application also supports adding, removing, and modifying the dictionary entries, making it flexible for users working with multiple subtitle languages or styles.

Recent updates have introduced batch processing, non-Indonesian word logging, and highlighting of unknown words, improving the user experience significantly.

## Features

- **File Upload**: Upload `SRT` or `ASS` subtitle files. The application will automatically detect and parse the subtitle file format.
- **Dictionary Management**:
  - Add new words to the dictionary to automatically replace terms in the subtitles.
  - Remove existing dictionary entries and update the subtitle text accordingly.
  - Manage dictionary items with an easy-to-use interface.
- **Highlighting Word Replacement**: Replace words in the subtitle file using the dictionary, and apply optional highlights to the replacements.
- **Batch File Processing**: Upload and process multiple subtitle files in one go, with batch downloading options available. Files are processed in parallel and saved as either `SRT` or `ASS` formats.
- **Download Subtitles**: After processing the subtitles, you can download them in either `SRT` or `ASS` format, with all replacements applied.
- **Highlighting Non-Indonesian Words**: The application can highlight words that ready to replaced in the subtitles using a specific style. It can detect typos, misspellings and other Indonesian words so that users can modify them directly. You can enable or disable this feature via the `ENABLE_WORD_HIGHLIGHT` constant.
- **Logging Non-Indonesian Words**: If you are working with Indonesian subtitles, the application can log non-Indonesian words when `ENABLE_NON_INDONESIAN_WORD_LOGGING` is enabled. The logs will be stored in `content/logs/`.
- **Clear Session**: Clear the session to start fresh with new subtitle files or dictionary entries.

## Setup Instructions

1. **Create the Dictionary File**:
   Ensure that a `dictionary.json` file exists in the `content/json/` directory. This file will store all the words and their replacements for use in the subtitle files. Example content for the file:

   ```json
   {
     "Hey how's it going": "Good morning"
   }
   ```

2. **Upload Subtitle Files**:
   You can upload your subtitle files (`SRT` or `ASS` format) using the upload interface. The application will automatically process these files, parse their content, and make it available for editing or downloading.

3. **Dictionary Management**:

   - **Add to Dictionary**: You can add new word pairs (e.g., replace `Hey` with `Hello`) through the form on the interface. The dictionary entries are saved to `dictionary.json`.
   - **Remove from Dictionary**: If you no longer need a replacement, simply remove the entry, and the changes will reflect in the subtitles automatically.

4. **Batch File Processing**:

   - **Upload Multiple Files**: You can upload multiple subtitle files at once. The files will be processed in parallel, and their replacements will be applied.
   - **Batch Download**: After processing, you can download all the modified files in a zip archive. The batch process allows downloading in `SRT` or `ASS` format.

5. **Highlighting Non-Indonesian Words**:
   If you are working with Indonesian subtitles, you can enable the highlighting of unknown words (words not found in the Indonesian dictionary) in the subtitle text. The application can also log these unknown words for future review if logging is enabled in the configuration.

6. **Clear Session**:
   If you want to start fresh or reset the dictionary word, you can clear the session, which will delete any files and dictionary changes.

7. **Download Processed Subtitles**:
   After processing the subtitles, you can download them in your desired format (`SRT` or `ASS`). The subtitle text will reflect the word replacements based on the current dictionary.

## Troubleshooting

- **Dictionary Not Working**: If the dictionary is not being applied, try clearing the session and re-uploading the subtitle file. This will ensure that the dictionary is properly loaded and applied to the subtitle content.
- **Empty Dictionary**: If the dictionary file is missing or empty, the application will still function but without any word replacements.
- **Batch Download Not Working**: Ensure that all uploaded files are in a valid format (`SRT` or `ASS`). The batch download functionality will only process files of these formats.

## Folder Structure

- `content/json/`: Store the dictionary file (`dictionary.json`) here.
- `content/logs/`: Logs for non-Indonesian words are stored here.

## Technologies Used

- PHP for server-side logic
- Session handling to store and manage subtitle and dictionary data
- JSON for dictionary storage
- ZipArchive for batch downloading multiple files as a ZIP archive
- `Sastrawi` library for Indonesian language stemming (for highlighting unknown words)

## License

This project is open-source. Feel free to fork and modify as needed for your personal use.

## Acknowledgments

- **Sastrawi Library**: Used for Indonesian language processing (stemmers and dictionary matching).
- **PHP & ZipArchive**: Utilized for file processing and handling batch downloads.
