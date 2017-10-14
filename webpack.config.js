var glob = require("glob");
module.exports = {
    context: __dirname + "/src/public/js",
    entry: glob.sync("./src/public/js/*.js"),
    output: {
        path: __dirname + "/dist/public/js/",
        filename: "[name].js"
    }
};
