# Cortex Analyst Plug-In Installation Guide

## Prerequisites

-   Ensure your **Moodle instance** is running.
-   You are logged in as **Admin** using the username and password
    specified in the `moodle.yaml` file.

------------------------------------------------------------------------

## Step 1: Configure Moodle Security

1.  Navigate to **Site Administration → General → Security → HTTP
    Security**.
2.  Perform the following:
    -   **Uncheck** `Secure cookies only`.
    -   **Clear** all IP addresses in `cURL blocked hosts list`.
3.  Click **Save Changes**.

------------------------------------------------------------------------

## Step 2: Prepare Data Files

1.  Download the **Data Files** provided with this guide.
2.  Ensure the **`moodle_role`** role is selected.

------------------------------------------------------------------------

## Step 3: Load Data into Snowflake

1.  Open **Snowflake Snowsight**.
2.  From the left navigation bar, click on **Data → Add Data**.
3.  Select **Load files into a stage** and upload the files.\
    \> ⚠️ Ensure the path to the folder is specified correctly.

------------------------------------------------------------------------

## Step 4: Execute SQL Setup

Run the following commands in **Snowflake Snowsight**:

``` sql
USE ROLE SECURITYADMIN;
GRANT DATABASE ROLE SNOWFLAKE.CORTEX_USER TO ROLE moodle_role;

USE ROLE MOODLE_ROLE;
USE DATABASE moodle_app;

-- Fact table: daily_revenue
CREATE OR REPLACE TABLE moodle_app.public.daily_revenue (
    date DATE,
    revenue FLOAT,
    cogs FLOAT,
    forecasted_revenue FLOAT,
    product_id INT,
    region_id INT
);

-- Dimension table: product_dim
CREATE OR REPLACE TABLE moodle_app.public.product_dim (
    product_id INT,
    product_line VARCHAR(16777216)
);

-- Dimension table: region_dim
CREATE OR REPLACE TABLE moodle_app.public.region_dim (
    region_id INT,
    sales_region VARCHAR(16777216),
    state VARCHAR(16777216)
);
```

### Load CSV Data

``` sql
COPY INTO moodle_app.public.DAILY_REVENUE
FROM @MOODLE_APP.PUBLIC.MOUNTED
FILES = ('moodledata/daily_revenue.csv')
FILE_FORMAT = (
    TYPE=CSV,
    SKIP_HEADER=1,
    FIELD_DELIMITER=',',
    TRIM_SPACE=FALSE,
    FIELD_OPTIONALLY_ENCLOSED_BY=NONE,
    REPLACE_INVALID_CHARACTERS=TRUE,
    DATE_FORMAT=AUTO,
    TIME_FORMAT=AUTO,
    TIMESTAMP_FORMAT=AUTO,
    EMPTY_FIELD_AS_NULL=FALSE,
    ERROR_ON_COLUMN_COUNT_MISMATCH=FALSE
)
ON_ERROR=CONTINUE
FORCE=TRUE;

COPY INTO moodle_app.public.PRODUCT_DIM
FROM @MOODLE_APP.PUBLIC.MOUNTED
FILES = ('moodledata/product.csv')
FILE_FORMAT = ( ...same as above... )
ON_ERROR=CONTINUE
FORCE=TRUE;

COPY INTO moodle_app.public.REGION_DIM
FROM @MOODLE_APP.PUBLIC.MOUNTED
FILES = ('moodledata/region.csv')
FILE_FORMAT = ( ...same as above... )
ON_ERROR=CONTINUE
FORCE=TRUE;
```

------------------------------------------------------------------------

## Step 5: Install the Plug-In

1.  Zip the folder named **`cortexanalyst.zip`**.
2.  Follow the [Moodle Plugin Installation Guide](../../../moodle-local-setup/) to install it.
3.  During installation, add your **Snowflake hostname**.
4.  Once Moodle installed and running, copy this plugin(**`cortexanalyst.zip`**.) to the below mentioned path depending on your system
* **Windows (using a server stack like XAMPP):**
    ```
    C:\xampp\htdocs\<your_moodle_folder>\local\
    ```
* **macOS (using a server stack like MAMP):**
    ```
    /Applications/MAMP/htdocs/<your_moodle_folder>/local/
    ```
* **Linux (typical Apache setup):**
    ```
    /var/www/html/<your_moodle_folder>/local/
    ```
5. Moodle will auto detect the plugin and ask if you wish to install it
------------------------------------------------------------------------

## Step 6: Access the Plug-In

-   Navigate to:

        <ingressurl>/local/cortexanalyst/index.php

------------------------------------------------------------------------

## Sample Query

Try asking: \> *For each month, what was the lowest daily revenue and on
what date did that lowest revenue occur?*

------------------------------------------------------------------------

✅ You are now ready to use **Cortex Analyst Plugin** with Moodle and
Snowflake!
