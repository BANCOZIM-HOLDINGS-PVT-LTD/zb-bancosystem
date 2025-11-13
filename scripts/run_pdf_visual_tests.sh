#!/bin/bash

# Script to run PDF visual tests in a CI/CD pipeline
# Usage: ./run_pdf_visual_tests.sh [--template=template_name] [--dataset=dataset_type] [--threshold=value] [--report] [--batch]

# Set default values
TEMPLATE=""
DATASET="default"
THRESHOLD=""
REPORT="--report"
BATCH=""

# Parse command line arguments
for arg in "$@"
do
    case $arg in
        --template=*)
        TEMPLATE="${arg#*=}"
        shift
        ;;
        --dataset=*)
        DATASET="${arg#*=}"
        shift
        ;;
        --threshold=*)
        THRESHOLD="--threshold=${arg#*=}"
        shift
        ;;
        --report)
        REPORT="--report"
        shift
        ;;
        --no-report)
        REPORT=""
        shift
        ;;
        --batch)
        BATCH="--batch"
        shift
        ;;
        *)
        # Unknown option
        echo "Unknown option: $arg"
        echo "Usage: ./run_pdf_visual_tests.sh [--template=template_name] [--dataset=dataset_type] [--threshold=value] [--report] [--batch]"
        exit 1
        ;;
    esac
done

# Build the command
COMMAND="php artisan pdf:visual-tests"

# Add template if specified
if [ ! -z "$TEMPLATE" ]; then
    COMMAND="$COMMAND --template=$TEMPLATE"
fi

# Add dataset
COMMAND="$COMMAND --dataset=$DATASET"

# Add threshold if specified
if [ ! -z "$THRESHOLD" ]; then
    COMMAND="$COMMAND $THRESHOLD"
fi

# Add report flag if specified
if [ ! -z "$REPORT" ]; then
    COMMAND="$COMMAND $REPORT"
fi

# Add batch flag if specified
if [ ! -z "$BATCH" ]; then
    COMMAND="$COMMAND $BATCH"
fi

# Print the command
echo "Running command: $COMMAND"

# Run the command
eval $COMMAND

# Get the exit code
EXIT_CODE=$?

# Print summary
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "\n✅ All PDF visual tests passed!"
else
    echo -e "\n❌ Some PDF visual tests failed. Check the reports for details."
fi

# Return the exit code
exit $EXIT_CODE