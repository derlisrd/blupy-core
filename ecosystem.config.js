module.exports = {
    apps: [
      {
        name: 'blupy-core-queue-worker',
        script: 'artisan',
        args: 'queue:work',
        interpreter: 'php',
        watch: false,
      }
    ]
  };
