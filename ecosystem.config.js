module.exports = {
    apps: [
      {
        name: 'laravel-queue-worker',
        script: 'artisan',
        args: 'queue:work',
        interpreter: 'php',
        watch: false,
      },
/*       {
        name: 'laravel-scheduler',
        script: 'artisan',
        args: 'schedule:run',
        interpreter: 'php',
        watch: false,
      } */
    ]
  };
