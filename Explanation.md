# Crawler Wordpress Plugin with Package Template

## **The problem to be solved in my own words**

- Create an admin access page on the WordPress dashboard
  where the admin can login, trigger a button that will
  run a script that crawls the homepage(ideally the entire website)
  for internal links and view the result of the crawl.
- The crawl script should update the sitemap page
  and store the result temporarily in a database.
- The crawl script should run automatically hourly.
- Visitors(non-admins) should be able to view the sitemap page.

## **A technical spec of how I will solve it**

- Install a dynamic WP theme that will be crawled
- Create the admin pages and settings
- Write the crawl script

## **The technical decisions I made and why**

- I will be using MySQL for the database.
- I have also make sure to not use any php third party libraries to showcase my native php coding abilities.
- I decided to create a Page menu instead of a Settings menu.

## **How the code itself works and why**

All of the crawl script can be found inside the crawl.php file.

I made sure to protect the `crawl.php` file from users accessing it without the admin privileges.
So here's how the code works:

- First I scrap the homepage using `curl` (code below).
- Then I create a DOM element using the html response I got.
- After that I extract the links by targeting the `<a>` tags.
- I then filter all the links and only keep those who are internal links
- Finally I store them temporaly to the database and use them to create the sitemap file.
- I also setup an WordPress event that launches the crawl `hourly`.

## **How the solution achieves the admin’s desired outcome per the user story**

To summerize the solution needed in one sentence: I need the admin of the website to be able to see a list of all the internal links inside the homepage and generate a sitemap.html file that can be viewed by any user.

Here is how I achieved that:

- When logged in, the admin can click on the `Launch Crawl` button to launch the crawl session and setup the hourly cronjob.
- After the crawl is done, the admin can see the results.
  ![Crawl Plugin](screenshots/Plugin%20Results.png)

- Anyone can also see the sitemap.html file.
- There is also the ability to toggle some options:
  ![Crawl Plugin](screenshots/Plugin%20Options.png)

## What I wasn't able to accomplish or make correctly work (yet)

`🔻 Make the script recursively work correctly` => For now the plugin only crawl the homepage unfortunately, I'm still working on that and trying to make it work.

`🔻 It’s built with modern OOP with PSR. It uses procedural where it makes sense.` =>
I did use OOP most of the time, but I'm not sure if I used Procedural where it makes sense. I started learning more about that.

`🔻 Your app or plugin passes phpcs inspection.` =>
I fixed most of the phpcs inspection issues but I'm searching for a way to make it automatically fix everything. I'm using vscode but I couldn't make the formatting plugins work: may be it's the way my computer is set up, I'll surely find a solution soon.

`🔻 Automatically test that your code works as expected by writing unit and integration tests.` =>
I don't have experience write test for PHP, I know we use PHPUnit to write tests. I'm investing some time this week to learn it.

`🔻 Wire your GitHub repo to Travis CI.` =>
Learning how to do that effectively.

`🔻 It’s built using our package template (see the README.md).` =>
I couldn't correctly setup the package template: want to ask for help about this.
