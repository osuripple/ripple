# Load settings
BACKUP_DATABASE=$(awk -F "=" '/backup_database/ {print $2}' config.ini)
BACKUP_REPLAYS=$(awk -F "=" '/backup_replays/ {print $2}' config.ini)

DB_USERNAME=$(awk -F "=" '/db_username/ {print $2}' config.ini)
DB_PASSWORD=$(awk -F "=" '/db_password/ {print $2}' config.ini)
DB_NAME=$(awk -F "=" '/db_name/ {print $2}' config.ini)

BACKBLAZE_ENABLE=$(awk -F "=" '/backblaze_enable/ {print $2}' config.ini)
BACKBLAZE_BUCKET_NAME=$(awk -F "=" '/backblaze_bucket_name/ {print $2}' config.ini)

S3_ENABLE=$(awk -F "=" '/s3_enable/ {print $2}' config.ini)
S3_BUCKET_NAME=$(awk -F "=" '/s3_bucket_name/ {print $2}' config.ini)

LOCAL_ENABLE=$(awk -F "=" '/local_enable/ {print $2}' config.ini)
LOCAL_FOLDER=$(awk -F "=" '/local_folder/ {print $2}' config.ini)

EMAIL_ENABLE=$(awk -F "=" '/email_enable/ {print $2}' config.ini)


# Variables
WHEN=$(date '+%F--%H-%M-%S')

# First, let's create a directory, cd to it and empty it
echo "Creating temp directory..."
mkdir temp
cd temp
rm -rf *

# Database backup
if [ $BACKUP_DATABASE = true ]; then
	echo "Dumping database..."
	mkdir db
	mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_NAME > "db/db-$WHEN.sql"
fi

# Replays backup
if [ $BACKUP_REPLAYS = true ]; then
	echo "Copying replays..."
	mkdir replays
	cp ../../osu.ppy.sh/replays/* replays
fi

# Done, let's tar this
echo "Compressing backup..."
tar -zcvf backup-$WHEN.tar.gz *

# Backup upload
if [ $BACKBLAZE_ENABLE = true ]; then
	# Upload backup to backblaze
	echo "Uploading backup archive to backblaze..."
	b2 upload_file $BACKBLAZE_BUCKET_NAME backup-$WHEN.tar.gz backup-$WHEN.tar.gz
fi

if [ $S3_ENABLE = true ]; then
	# Upload backup to S3
	echo "Uploading backup archive to S3..."
	aws s3 cp backup-$WHEN.tar.gz $S3_BUCKET_NAME
fi

if [ $LOCAL_ENABLE = true ]; then
	# Copy backup to local folder
	echo "Copying backup to local folder..."
	cp backup-$WHEN.tar.gz $LOCAL_FOLDER
fi

# Exit temp folder
cd ..

if [ $EMAIL_ENABLE = true ]; then
	# Send email
	echo "Sending email..."
	php send-email.php $(du -BM temp/backup-$WHEN.tar.gz | cut -f1)
fi

# Delete temp folder
echo "Deleting temp files..."
rm -rf temp
echo "Backup completed!"
