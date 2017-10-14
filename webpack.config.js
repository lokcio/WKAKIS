module.exports = {
    context: __dirname + "/src/public/js",
    entry: "./test.js",
    output: {
        path: __dirname + "/dist/public/js/",
        filename: "test.js"
    }
};
