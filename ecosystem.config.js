module.exports = {
    apps: [
      {
        name: 'laravel-queue-worker',
        script: 'artisan',
        args: 'queue:work --tries=3',
        interpreter: 'php',
        watch: false,
      },
      {
        name: 'laravel-scheduler',
        script: 'artisan',
        args: 'schedule:work',
        interpreter: 'php',
        watch: false,
      }
    ]
  };
