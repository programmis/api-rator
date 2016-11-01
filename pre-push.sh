#!/bin/sh
echo "--- RUN TESTS ---"

RESULT=$(php ./run-tests.php)
if [ $RESULT -ne 0 ]; then
    echo "--- TESTS FAIL ---"
    exit 1
fi

echo "--- TESTS PASSED ---"
exit 0
