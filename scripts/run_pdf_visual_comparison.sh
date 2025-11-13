#!/bin/bash

# Script to run PDF visual comparison tests with different data sets
# Usage: ./run_pdf_visual_comparison.sh [--template=template_name] [--dataset=dataset_type] [--threshold=value] [--report] [--output=directory]

# Set default values
TEMPLATE=""
DATASET="all"
THRESHOLD=""
REPORT="--report"
OUTPUT_DIR="./storage/app/temp/pdf-visual-tests/reports"

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
        --output=*)
        OUTPUT_DIR="${arg#*=}"
        shift
        ;;
        --help)
        echo "PDF Visual Comparison Test Runner"
        echo "================================="
        echo "Usage: ./run_pdf_visual_comparison.sh [options]"
        echo ""
        echo "Options:"
        echo "  --template=NAME    Test specific template (zb_account_opening, ssb, sme_account_opening, account_holders)"
        echo "  --dataset=TYPE     Test specific dataset type (default, edge_case, variation, all)"
        echo "  --threshold=VALUE  Set difference threshold percentage (0-100)"
        echo "  --report           Generate HTML reports (default)"
        echo "  --no-report        Skip HTML report generation"
        echo "  --output=DIR       Set output directory for reports"
        echo "  --help             Show this help message"
        exit 0
        ;;
        *)
        # Unknown option
        echo "Unknown option: $arg"
        echo "Use --help for usage information"
        exit 1
        ;;
    esac
done

# Create output directory if it doesn't exist
mkdir -p "$OUTPUT_DIR"

# Build the command
COMMAND="php artisan pdf:visual-comparison-test"

# Add template if specified
if [ ! -z "$TEMPLATE" ]; then
    COMMAND="$COMMAND --template=$TEMPLATE"
fi

# Add dataset if specified
if [ ! -z "$DATASET" ]; then
    COMMAND="$COMMAND --dataset=$DATASET"
fi

# Add threshold if specified
if [ ! -z "$THRESHOLD" ]; then
    COMMAND="$COMMAND $THRESHOLD"
fi

# Add report flag if specified
if [ ! -z "$REPORT" ]; then
    COMMAND="$COMMAND $REPORT"
fi

# Add output directory
COMMAND="$COMMAND --output=$OUTPUT_DIR"

# Run the command
echo "Running: $COMMAND"
$COMMAND

# Get exit code
EXIT_CODE=$?

# Return exit code
exit $EXIT_CODE